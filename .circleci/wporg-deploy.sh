#!/usr/bin/env bash
#
# Deploy this plugin to the WordPress.org plugin directory (Subversion).
#
# This follows the same steps as 10up/action-wordpress-plugin-deploy
# (deploy.sh, 2.3.0): check out the plugin's SVN repository, mirror the
# Git working tree into trunk/ (minus .distignore), mirror .wordpress-org/
# into assets/, copy trunk/ to tags/<version>, and commit everything as one
# SVN commit. It is kept in this repository instead of pulled from a
# third-party orb or action, so the code that sees the SVN password is the
# code you can read here.
#
# Environment:
#   SLUG          WordPress.org plugin slug (required)
#   PLUGIN_FILE   Main plugin file holding the "Version:" header (required)
#   DRY_RUN       "true" to stop before `svn commit` (default: false)
#   CIRCLE_TAG    Git tag being released; required unless DRY_RUN=true
#   SVN_USERNAME  WordPress.org username (required unless DRY_RUN=true)
#   SVN_PASSWORD  WordPress.org SVN password (required unless DRY_RUN=true)
#   ASSETS_DIR    Directory mirrored to assets/ (default: .wordpress-org)
#   OUT_DIR       Where the release zip is written (default: /tmp/wporg-out)
#   WPORG_SVN_URL SVN repository URL, for testing against a local repository
#                 (default: https://plugins.svn.wordpress.org/$SLUG/)
#
# Never run this with `set -x`: it would print the SVN password.

set -euo pipefail

DRY_RUN="${DRY_RUN:-false}"
ASSETS_DIR="${ASSETS_DIR:-.wordpress-org}"
OUT_DIR="${OUT_DIR:-/tmp/wporg-out}"
: "${SLUG:?Set SLUG}"
: "${PLUGIN_FILE:?Set PLUGIN_FILE}"
SVN_URL="${WPORG_SVN_URL:-https://plugins.svn.wordpress.org/${SLUG}/}"

WORKSPACE="$(pwd)"
SVN_DIR="$(mktemp -d)/svn-${SLUG}"

if [[ ! -f "$WORKSPACE/.distignore" ]]; then
	echo "✗ .distignore not found in $WORKSPACE" >&2
	exit 1
fi

# --- Versions -------------------------------------------------------------
# WordPress.org serves whatever readme.txt's "Stable tag" in trunk names, so a
# release whose Stable tag still points at the old version uploads the new tag
# but keeps users on the old code. Refuse to release unless all three agree.

header_version="$(grep -m1 -E '^[[:space:]]*\*?[[:space:]]*Version:' "$PLUGIN_FILE" | sed -E 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')"
stable_tag="$(grep -m1 -iE '^Stable tag:' readme.txt | sed -E 's/^[^:]*:[[:space:]]*//' | tr -d '[:space:]')"

if [[ -n "${CIRCLE_TAG:-}" ]]; then
	VERSION="${CIRCLE_TAG#v}"
	if [[ "$VERSION" != "$header_version" || "$VERSION" != "$stable_tag" ]]; then
		echo "✗ Version mismatch: tag=$VERSION, $PLUGIN_FILE Version=$header_version, readme.txt Stable tag=$stable_tag" >&2
		echo "  Make all three the same, then tag again." >&2
		exit 1
	fi
elif [[ "$DRY_RUN" == "true" ]]; then
	VERSION="$header_version"
	if [[ "$VERSION" != "$stable_tag" ]]; then
		echo "⚠ readme.txt Stable tag ($stable_tag) differs from Version ($VERSION)."
		echo "  A release tagged $VERSION would fail until Stable tag is $VERSION."
	fi
else
	echo "✗ CIRCLE_TAG is empty: a real deploy runs only from a tag." >&2
	exit 1
fi
echo "ℹ︎ SLUG is $SLUG, VERSION is $VERSION, DRY_RUN is $DRY_RUN"

if [[ "$DRY_RUN" != "true" ]]; then
	: "${SVN_USERNAME:?Set SVN_USERNAME in the CircleCI context}"
	: "${SVN_PASSWORD:?Set SVN_PASSWORD in the CircleCI context}"
fi

# --- Release zip, built from this commit ----------------------------------
# Built from the Git tree rather than from SVN trunk, so a re-run of an
# older tag still produces that tag's files.

mkdir -p "$OUT_DIR"
zip_root="$(mktemp -d)"
rsync -rc --exclude-from="$WORKSPACE/.distignore" "$WORKSPACE/" "$zip_root/$SLUG/" --delete --delete-excluded
(cd "$zip_root" && zip -qr "$OUT_DIR/$SLUG-$VERSION.zip" "$SLUG")
echo "✓ Built $OUT_DIR/$SLUG-$VERSION.zip"

# --- SVN checkout ---------------------------------------------------------

echo "➤ Checking out $SVN_URL"
svn checkout --quiet --non-interactive --depth immediates "$SVN_URL" "$SVN_DIR"
cd "$SVN_DIR"
svn update --quiet --non-interactive --set-depth infinity assets
svn update --quiet --non-interactive --set-depth infinity trunk
svn update --quiet --non-interactive --set-depth immediates tags

if [[ -d "tags/$VERSION" ]]; then
	if [[ "$DRY_RUN" == "true" ]]; then
		echo "ℹ︎ tags/$VERSION already exists in SVN; showing the trunk diff only."
	else
		echo "ℹ︎ Version $VERSION of $SLUG is already published; nothing to do."
		exit 0
	fi
fi

# --- Mirror files ---------------------------------------------------------

echo "➤ Copying files to trunk/"
rsync -rc --exclude-from="$WORKSPACE/.distignore" "$WORKSPACE/" trunk/ --delete --delete-excluded

if [[ -d "$WORKSPACE/$ASSETS_DIR" ]]; then
	echo "➤ Copying $ASSETS_DIR/ to assets/"
	rsync -rc "$WORKSPACE/$ASSETS_DIR/" assets/ --delete
else
	echo "ℹ︎ No $ASSETS_DIR directory; leaving assets/ as it is"
fi

svn add . --force --quiet
# Schedule removal of files that rsync deleted. The trailing @ keeps SVN from
# reading an @ inside a file name as a peg revision.
svn status | { grep '^!' || true; } | sed 's/^! *//' | while IFS= read -r path; do
	svn rm --quiet "${path}@"
done

if [[ ! -d "tags/$VERSION" ]]; then
	svn cp --quiet trunk "tags/$VERSION"
fi

# Serve screenshots and banners inline instead of as downloads.
# https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/
if [[ -d assets ]]; then
	shopt -s nullglob
	for f in assets/*.png; do svn propset --quiet svn:mime-type image/png "$f"; done
	for f in assets/*.jpg assets/*.jpeg; do svn propset --quiet svn:mime-type image/jpeg "$f"; done
	for f in assets/*.gif; do svn propset --quiet svn:mime-type image/gif "$f"; done
	for f in assets/*.svg; do svn propset --quiet svn:mime-type image/svg+xml "$f"; done
	shopt -u nullglob
fi

svn update --quiet --non-interactive
echo "➤ Changes that would be committed:"
svn status

if [[ "$DRY_RUN" == "true" ]]; then
	echo "✓ Dry run: nothing was committed."
	exit 0
fi

echo "➤ Committing $VERSION to WordPress.org"
# --password-from-stdin keeps the password out of the process list.
printf '%s' "$SVN_PASSWORD" | svn commit \
	--non-interactive --no-auth-cache \
	--username "$SVN_USERNAME" --password-from-stdin \
	-m "Update to version $VERSION from CircleCI"

echo "✓ Committed. If release confirmation emails are on for this plugin, approve the release from the email WordPress.org sends."
