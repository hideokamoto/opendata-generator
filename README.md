# OpenData Generator v3.0.2

WordPress plugin for generating JSON-LD (Linked Open Data) from custom fields.

## About

This plugin generates JSON-LD files from WordPress custom fields, making it easy to publish structured open data from your WordPress site.

## Features

- ✅ Generate JSON-LD from WordPress custom fields
- ✅ Support for Schema.org vocabulary (default)
- ✅ Support for custom RDF schemas
- ✅ Query filtering using WP_Query parameters
- ✅ Support for @graph parameter for multiple schemas
- ✅ Admin interface for schema and mapping management
- ✅ Security: Input sanitization and validation
- ✅ Internationalization ready

## Installation

1. Download the ZIP file
2. Install as a WordPress plugin via the admin panel
3. Activate the plugin
4. The plugin will start outputting JSON-LD data

## Usage

### Accessing JSON-LD Data

- All posts: `http://[your-domain]/odg-jsonld/`
- Single post: `http://[your-domain]/[post-url]/odg-jsonld/`
- Context definition: `http://[your-domain]/odg-jsonld-context/`

### Filtering Posts

You can filter posts using WP_Query parameters via the `filter` parameter:

```
http://[your-domain]/odg-jsonld/?filter[cat]=1&filter[posts_per_page]=10
```

#### Available Filter Parameters

Secure whitelist of allowed parameters:

- `posts_per_page`, `post_type`, `post_status`
- `cat`, `category_name`, `tag`, `tag_id`
- `author`, `author_name`
- `year`, `monthnum`, `day`, `hour`, `minute`, `second`
- `name`, `pagename`, `page_id`, `p`
- `order`, `orderby`
- `s` (search), `exact`, `sentence`

For detailed parameter information, see [WP_Query documentation](https://developer.wordpress.org/reference/classes/wp_query/).

### Setting Up Schemas

1. Go to **OpenData Generator > Schema Settings** in WordPress admin
2. Add vocabulary name and IRI (e.g., "schema" → "http://schema.org/")
3. Save settings

Default: Schema.org (`http://schema.org/`)

### Creating Mappings

1. Go to **OpenData Generator > Create Mapping**
2. Set the title as the @type (e.g., "Event", "Place")
3. Add custom fields mapping your content fields to schema properties
4. Format: `vocabulary:property` (e.g., `schema:name`, `schema:description`)

### Custom Field Format

Use the format `vocabulary:property` for custom field names:

**Example:**
- Field name: `schema:name`
- Field value: `My Event Title`

This will generate:
```json
{
  "@type": "Event",
  "name": "My Event Title",
  "@context": "http://your-site.com/odg-jsonld-context/"
}
```

## Configuration

### Admin Panel

The plugin adds a menu item **OpenData Generator** with the following subpages:

- **Dashboard**: Overview and instructions
- **Schema Settings**: Configure RDF vocabularies
- **Mapping List**: View all field mappings
- **Create Mapping**: Create new field mapping

## Security

- ✅ Input sanitization and validation
- ✅ Nonce verification for admin actions
- ✅ Whitelist-based query parameter filtering
- ✅ XSS prevention
- ✅ SQL injection prevention

## Development

### Running Tests

```bash
phpunit
```

### Requirements

- WordPress 4.1.2 or higher
- PHP 5.6 or higher

## Examples

### Events

Custom Fields:
- `schema:name` → "WordPress Meetup Tokyo"
- `schema:startDate` → "2024-01-15"
- `schema:location` → "Tokyo, Japan"

Output:
```json
{
  "@context": "http://schema.org/",
  "@type": "Event",
  "name": "WordPress Meetup Tokyo",
  "startDate": "2024-01-15",
  "location": "Tokyo, Japan"
}
```

## License

GPL v2 or later. See LICENSE.md for details.

## Changelog

### 3.0.2
- Fix activation error
- Security improvements
- Bug fixes for @graph and @type handling
- Enhanced input sanitization
- Added comprehensive tests
- Added error handling
- Added uninstall hook

### 3.0.0
- Full remake
- Support @graph parameter
- Support Schema Mapping page
- Only show root content

### 2.0.0
- Remake (no backward compatibility with v1)

### 1.5
- Support WP_Query parameters

### 1.0
- Initial release

## Support

For issues and feature requests, please visit:
https://github.com/hideokamoto/opendata-generator/

## Author

Hidetaka Okamoto
- Website: http://wp-kyoto.net/
- GitHub: https://github.com/hideokamoto/
