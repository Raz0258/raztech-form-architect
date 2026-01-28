=== RazTech AI Form Architect ===
Contributors: raztech
Donate link: https://raz-technologies.com/
Tags: forms, ai form builder, contact form, lead scoring, ai generator
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Intelligent form builder with AI-powered features, lead scoring, spam detection, and analytics.

== Description ==

RazTech AI Form Architect is a powerful WordPress form builder that works great out of the box, with optional AI enhancements.

= Core Features (No API Required) =

* Visual drag & drop form builder
* 10+ field types
* Submission management with filtering
* CSV export
* Pattern-based spam detection (95% accuracy)
* Automatic lead quality scoring
* Form analytics dashboard
* Mobile responsive
* Email notifications

= Optional AI Features =

Connect your own OpenAI or Anthropic API key to unlock:

* AI form generation
* Enhanced spam detection (99% accuracy)
* Personalized auto-responses
* Smart insights

= External Services (Optional) =

This plugin CAN connect to external services. All are optional and require your API keys:

**OpenAI API** (https://api.openai.com/)

* Used for: AI form generation, spam detection, auto-responses
* Data sent: Form configurations, submission content (only when enabled)
* Privacy: https://openai.com/policies/privacy-policy
* Terms: https://openai.com/policies/terms-of-use

**Anthropic Claude API** (https://api.anthropic.com/)

* Used for: AI form generation
* Data sent: Form generation prompts (only when enabled)
* Privacy: https://www.anthropic.com/legal/privacy
* Terms: https://www.anthropic.com/legal/terms

The plugin works completely without any external services. AI features are optional. All assets including Chart.js are bundled locally.

= Perfect For =

* Lead generation forms
* Contact forms
* Quote requests
* Surveys and feedback
* Newsletter signups

== Installation ==

= Automatic Installation =

1. Go to Plugins > Add New
2. Search for "RazTech AI Form Architect"
3. Click Install and Activate
4. Go to RazTech AI Form Architect > Add New Form

= Manual Installation =

1. Download the ZIP file
2. Go to Plugins > Add New > Upload
3. Upload and activate
4. Go to RazTech AI Form Architect > Add New Form

= Basic Setup =

1. Create a form using the visual builder
2. Copy the shortcode
3. Paste into any page or post

= Email Setup (Recommended) =

For reliable emails, install WP Mail SMTP or similar SMTP plugin.

= AI Setup (Optional) =

1. Get API key from OpenAI or Anthropic
2. Go to RazTech AI Form Architect > Settings
3. Enter your API key
4. Save settings

== Frequently Asked Questions ==

= Do I need an API key? =

No! The plugin works fully without API keys. AI features are optional enhancements.

= How much do AI features cost? =

The plugin is free. AI features use your own API key. Typical costs: $0.50-$5/month depending on usage.

= Does it work with my theme? =

Yes! Works with all WordPress themes.

= How accurate is spam detection? =

95% without AI, 99% with AI content analysis.

= Can I export submissions? =

Yes! Click "Export to CSV" on the Submissions page.

= Is it GDPR compliant? =

Yes. All data stored in your WordPress database. Disclose AI usage in your privacy policy if enabled.

== Screenshots ==

1. Dashboard with analytics and insights
2. Visual drag & drop form builder
3. AI form generation modal
4. Submission management with lead scoring
5. Settings panel

== Changelog ==

= 1.0.2 =
* Security: Added SQL query whitelisting for orderby parameters
* Security: Implemented recursive JSON sanitization for form fields
* Security: Localized Chart.js library (removed CDN dependency)
* Security: Moved inline scripts to proper WordPress enqueue system
* Fix: Late escaping in templates-library.php
* Enhancement: Complete prefix migration from rt_fa to raztaifo
* Enhancement: Updated domain to raz-technologies.com
* WordPress.org reviewer feedback implemented

= 1.0.1 =
* Fix: Removed Plugin URI to resolve conflict with Author URI
* WordPress.org submission compliance update

= 1.0.0 =
* Initial release. Refactored for security, naming compliance, and performance.
* Visual drag & drop form builder
* AI-powered form generation (optional)
* Automatic lead quality scoring
* Pattern-based spam detection (95% accuracy, 99% with AI)
* Analytics dashboard with insights
* CSV export functionality
* Email notifications with auto-responder
* Mobile responsive design
* WordPress 6.7 compatible

== Upgrade Notice ==

= 1.0.0 =
Initial release of RazTech AI Form Architect.
