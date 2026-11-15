# OpenData Generator - Usage Guide

Complete guide to using OpenData Generator plugin for WordPress.

## Table of Contents

1. [Quick Start](#quick-start)
2. [Step-by-Step Tutorial](#step-by-step-tutorial)
3. [Advanced Usage](#advanced-usage)
4. [Examples](#examples)
5. [Troubleshooting](#troubleshooting)

## Quick Start

### Installation

1. Upload plugin to `/wp-content/plugins/opendata-generator/`
2. Activate through WordPress admin panel
3. Go to **OpenData Generator** menu

### Basic Setup

1. **Define Schema** (OpenData Generator > Schema Settings)
   - Default: Schema.org is pre-configured
   - Add custom vocabularies if needed

2. **Create Mapping** (OpenData Generator > Create Mapping)
   - Title: @type name (e.g., "Event", "Place")
   - Custom Fields: map to schema properties

3. **Add Content** (Posts > Add New)
   - Add custom fields matching your mapping
   - Format: `vocabulary:property`

4. **Access JSON-LD**
   - View at: `http://your-site.com/odg-jsonld/`

## Step-by-Step Tutorial

### Example: Publishing Event Data

Let's create JSON-LD for events using Schema.org's Event type.

#### Step 1: Verify Schema Settings

1. Go to **OpenData Generator > Schema Settings**
2. Confirm Schema.org is configured:
   - Vocabulary Name: `schema`
   - URI: `http://schema.org/`
3. Click **Save Change** if you added anything

#### Step 2: Create Event Mapping

1. Go to **OpenData Generator > Create Mapping**
2. Set **Title**: `Event`
3. Add **Custom Fields**:

   | Custom Field Name | Custom Field Value |
   |-------------------|-------------------|
   | schema:name | name |
   | schema:description | description |
   | schema:startDate | startDate |
   | schema:endDate | endDate |
   | schema:location | location |
   | schema:organizer | organizer |

4. **Publish** the mapping

#### Step 3: Create Event Post

1. Go to **Posts > Add New**
2. **Title**: "WordPress Meetup Tokyo #50"
3. **Content**: (regular post content)
4. Add **Custom Fields**:

   | Name | Value |
   |------|-------|
   | schema:name | WordPress Meetup Tokyo #50 |
   | schema:description | Monthly WordPress community meetup in Tokyo |
   | schema:startDate | 2024-12-15T19:00:00+09:00 |
   | schema:endDate | 2024-12-15T21:00:00+09:00 |
   | schema:location | Shibuya, Tokyo, Japan |
   | schema:organizer | Tokyo WordPress Community |

5. **Publish** the post

#### Step 4: Verify JSON-LD Output

1. Visit: `http://your-site.com/odg-jsonld/`
2. You should see:

```json
[
  {
    "@type": "Event",
    "name": "WordPress Meetup Tokyo #50",
    "description": "Monthly WordPress community meetup in Tokyo",
    "startDate": "2024-12-15T19:00:00+09:00",
    "endDate": "2024-12-15T21:00:00+09:00",
    "location": "Shibuya, Tokyo, Japan",
    "organizer": "Tokyo WordPress Community",
    "@id": "http://your-site.com/wordpress-meetup-tokyo-50/",
    "@context": "http://your-site.com/odg-jsonld-context/"
  }
]
```

## Advanced Usage

### Multiple Schema Types (@graph)

You can map multiple schema types to a single post.

**Example**: Event with Place details

**Mapping 1: Event**
- Title: `Event`
- Custom Fields:
  - `schema:name` → `name`
  - `schema:startDate` → `startDate`

**Mapping 2: Place**
- Title: `Place`
- Custom Fields:
  - `schema:name` → `placeName`
  - `schema:address` → `address`

**Post Custom Fields**:
- `schema:name` → "Tech Conference 2024"
- `schema:startDate` → "2024-03-20"
- `schema:placeName` → "Convention Center"
- `schema:address` → "123 Main St"

**Output**:
```json
{
  "@graph": [
    {
      "@type": "Event",
      "name": "Tech Conference 2024",
      "startDate": "2024-03-20"
    },
    {
      "@type": "Place",
      "name": "Convention Center",
      "address": "123 Main St"
    }
  ],
  "@id": "http://your-site.com/tech-conference-2024/",
  "@context": "http://your-site.com/odg-jsonld-context/"
}
```

### Custom Vocabularies

Add vocabularies beyond Schema.org:

**Example: Dublin Core**

1. **OpenData Generator > Schema Settings**
2. Add new row:
   - Vocabulary Name: `dc`
   - URI: `http://purl.org/dc/elements/1.1/`
3. **Save Change**

4. Create mapping using `dc:title`, `dc:creator`, etc.

### Filtering Output

Filter posts by category, date, author, etc.

**Examples**:

```bash
# Category 5 only
/odg-jsonld/?filter[cat]=5

# Last 10 posts
/odg-jsonld/?filter[posts_per_page]=10

# Posts from 2024
/odg-jsonld/?filter[year]=2024

# Posts by author 2, ordered by title
/odg-jsonld/?filter[author]=2&filter[orderby]=title&filter[order]=ASC

# Search for "wordpress"
/odg-jsonld/?filter[s]=wordpress
```

### Custom Post Types

To use with custom post types:

**In Mapping**:
- Add custom field: `post_type` → `your_custom_type`

**In URL**:
```
/odg-jsonld/?filter[post_type]=your_custom_type
```

## Examples

### Example 1: Local Business

**Mapping**: LocalBusiness
- `schema:name` → `name`
- `schema:address` → `address`
- `schema:telephone` → `telephone`
- `schema:openingHours` → `openingHours`

**Post Custom Fields**:
- `schema:name` → "Cafe Mokuba"
- `schema:address` → "1-2-3 Shibuya, Tokyo"
- `schema:telephone` → "+81-3-1234-5678"
- `schema:openingHours` → "Mo-Su 09:00-22:00"

### Example 2: Article/BlogPosting

**Mapping**: BlogPosting
- `schema:headline` → `headline`
- `schema:datePublished` → `datePublished`
- `schema:author` → `author`
- `schema:articleBody` → `articleBody`

### Example 3: Product

**Mapping**: Product
- `schema:name` → `name`
- `schema:description` → `description`
- `schema:price` → `price`
- `schema:priceCurrency` → `priceCurrency`
- `schema:availability` → `availability`

## Troubleshooting

### Issue: No output at /odg-jsonld/

**Solutions**:
1. Check if any mappings exist (OpenData Generator > Mapping List)
2. Verify posts have custom fields matching the mapping
3. Try flushing permalinks (Settings > Permalinks > Save Changes)
4. Check for 404 errors in browser console

### Issue: Empty JSON-LD or missing fields

**Solutions**:
1. Verify custom field names match exactly (case-sensitive)
2. Check mapping has correct format: `vocabulary:property`
3. Ensure posts are published (not draft)
4. Check WP_DEBUG logs for errors

### Issue: Invalid JSON-LD structure

**Solutions**:
1. Validate output at: https://json-ld.org/playground/
2. Check for special characters in field values
3. Verify vocabulary URIs are correct
4. Test with minimal mapping first

### Issue: Security warnings

**Solutions**:
1. Update to latest version (3.0.2+)
2. Review filter parameters - only whitelisted params allowed
3. Enable WP_DEBUG to see detailed error logs

### Issue: Performance problems

**Solutions**:
1. Use `posts_per_page` filter to limit results
2. Consider caching plugin for JSON-LD endpoint
3. Optimize database queries
4. Use pagination for large datasets

## Best Practices

### 1. Data Quality

- Use proper date/time formats (ISO 8601)
- Provide complete information
- Validate URLs and email addresses
- Use consistent terminology

### 2. Schema Design

- Choose appropriate schema types
- Use standard vocabularies when possible
- Document custom vocabularies
- Keep mappings simple and clear

### 3. Performance

- Limit posts per page for large sites
- Use caching for static content
- Index custom fields in database
- Monitor query performance

### 4. Maintenance

- Test after WordPress updates
- Validate JSON-LD output regularly
- Keep documentation updated
- Back up mapping configurations

## Resources

- [Schema.org Documentation](https://schema.org/)
- [JSON-LD Specification](https://www.w3.org/TR/json-ld/)
- [JSON-LD Playground](https://json-ld.org/playground/)
- [Google Structured Data Testing Tool](https://search.google.com/test/rich-results)
- [WP_Query Parameters](https://developer.wordpress.org/reference/classes/wp_query/)

## Support

For issues, questions, or contributions:
- GitHub: https://github.com/hideokamoto/opendata-generator/
- Issues: https://github.com/hideokamoto/opendata-generator/issues
