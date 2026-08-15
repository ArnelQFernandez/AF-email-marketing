AF Email Marketing

A lightweight WordPress plugin for email opt-in forms, subscriber management, and newsletter sending — built for personal sites using one.com email hosting.

Features
Subscribe form — embed anywhere with [subscribe_form] shortcode
Subscriber management — view, search, delete, and export subscribers as CSV
Newsletter composer — rich text editor with save draft, send test, and send to all
one.com SMTP — pre-configured for send.one.com (port 465, SSL)
Welcome email — sent automatically on subscribe
Unsubscribe link — appended to every newsletter automatically
Auto-append — optionally show the form at the bottom of every blog post
No third-party dependencies — everything stored in your WordPress database
Requirements
WordPress 5.0 or higher
PHP 7.4 or higher
one.com email hosting (or any SMTP provider)
Installation
Download the latest zip from Releases
Go to WordPress Admin > Plugins > Add New > Upload Plugin
Upload the zip and click Install Now
Activate the plugin
Setup

After activating, go to Email Marketing > Settings and fill in your SMTP credentials:

Field	Value
SMTP Host	send.one.com
Port	465
Encryption	SSL
Username	your one.com email address
Password	your one.com email password
Usage
Embed the subscribe form

Add the shortcode to any post, page, or widget:

[subscribe_form]

Customize with optional attributes:

[subscribe_form title="Join the Newsletter" description="Weekly tips." button="Subscribe" style="default"]

Style options:

default — centered, gray box background
minimal — no box, left-aligned (default appearance)
boxed — left blue accent border
Auto-append to all posts

Go to Email Marketing > Settings and check Automatically show subscribe form at the bottom of every blog post.

Send a newsletter
Go to Email Marketing > Newsletter
Click + New Newsletter
Write your subject and body
Hit Send Test to preview in your inbox
Hit Send to All Subscribers when ready
Changelog
v1.4.0
Updated placeholder text to "Your first name" and "Your email address"
v1.3.0
Removed "(optional)" from first name placeholder
v1.2.0
Redesigned form layout: inline horizontal fields (name + email + button in one row)
Removed gray box from default form style
Added version-based cache busting for CSS and JS
v1.0.0
Initial release
License

GPL v2 or later
