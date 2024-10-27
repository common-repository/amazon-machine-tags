=== Plugin Name ===
Contributors: kliehm
Donate link: http://www.amazon.de/gp/registry/wishlist/18XSF4H22J4L8/
Tags: Amazon, API, web services, post, posts, sidebar, content, tags, tagging, tag, links, machine tags, associate, XSLT, XSL, REST, CSS, i18n, POSH, WAI, ARIA, Accessibility, accessible
Requires at least: 2.3
Tested up to: 2.9.1
Stable tag: 3.0.2

The plugin checks for machine tags with ISBN or ASIN numbers, gets the product data from Amazon, and displays it in the sidebar or in a blog article.

== Description ==

Simple inclusion of Amazon items through machine tags.

1. Identifies any tag in the [machine or triple tag](http://en.wikipedia.org/wiki/Machine_tag#Triple_tags) form `book:isbn=1234567890` or `amazon:asin=1234567890`. Works with native tags from WordPress 2.3 and later, Bunny’s Technorati Tags, and Jerome’s Keywords.
1. Gets the item information and a thumbnail image from the **Amazon Web Services API**.
1. Displays the item(s) in the sidebar or in a blog article with a link to the visitor’s best match (if the [ip2country](http://priyadi.net/archives/2005/02/25/wordpress-ip-to-country-plugin/) plugin is installed) or a default Amazon shop of your choice.
1. If you are an Amazon Associate for that locale, your Associate ID is included automatically.

You can edit the server-side, semantic and valid XHTML output via XSLT, change the CSS, or translate the admin interface through PO-files.

== Installation ==

1. Upload the whole `amazon-machine-tags` folder into the `wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress. Make sure that you don’t run it on `localhost` as XML files on the server need to be reached from the outside.
1. Get your own Amazon Web Services [Access Key Identifyers](http://aws-portal.amazon.com/gp/aws/developer/account/index.html?action=access-key).
1. Create a `wp-content/cache/` directory with permissions set to 755, or 777 if you create the directory as `root` user.
1. Put `<?php AMTAP::get_items(); ?>` in your sidebar and start using machine tags.
1. Edit `amtap-blog.css` if you like.
1. If you want to use tags in a blog article, write `[amtap book:isbn=1234567890]` or `[amtap amazon:asin=1234567890]`.

== Screenshots ==

1. Admin interface
2. The plugin in a sidebar

== Frequently Asked Questions ==

= Does it work in the sidebar on Pages, too? =

Posts have tags, pages don’t, so it doesn’t work on those by default. But there’s a plugin called [tags4page](http://wordpress.org/extend/plugins/tags4page/) that enables tags for pages. Works like a charm.

= Is there a limit of how many items can be requested? =

Yes, Amazon has a limit of 10 items per request. Since they are separate requests, you can use a maximum of 10 items in the content plus a maximum of 10 in the sidebar.

= Would it be possible to cache the images? =

Technically it wouldn’t be a problem, but the [Amazon Product Advertising API license](https://affiliate-program.amazon.com/gp/advertising/api/detail/agreement.html) explicitly forbids caching of images (see 5.1.10). Sorry.

= I need to a larger thumbnail (medium), but can’t seem to find a place to edit the size of the image being requested. =

The image size can be edited in the XSLT. The original result is a XML file that is transformed by Amazon using your local copy of `amtap-html-sidebar.xsl` and `amtap-html-content.xsl`, respectively. Replacing every occurance of `.//aws:TinyImage` with `.//aws:MediumImage` in lines 73-83 should do the trick.

You can view the original XML when you activate the “debug” option in the admin interface so that the request string is printed as a comment in the sidebar’s source code. XSLT is a very powerful tool, and there’s a lot more in the XML, for example customer reviews.

= Getting an error about “private IP address space”?

The error message means you are running the blog on something like `localhost`. Amazon Web Services needs to get an XML file from your server, obviously that is impossible when it’s not located on a server that can be accessed with a public IP address from the outside.

= Are all options really required? =

No. The only required fields are the Amazon Web Services Access Key ID and your Secret Access Key. You can leave the others, they are set to defaults then.

== Changelog ==

* 3.0.2: Bugfix in SHA256 calculation.
* 3.0.1: Fixed a bug in setting the timestamp that caused the key validation to fail. The included SHA256 encryption now has a GNU Lesser GPL.
* 3.0: Added signed requests for the new Amazon authorization requirement. Updated the API version to 2009-07-01 (please note: if you use your own XSL files, you must update the version in the XML namespace URL). Updated links.
* 2.0: Added fields for editing the sidebar headline, link target, and displaying rating stars. Added an error message if the plugin is run from a private IP address space. Changed priorities for price selection, they are now: `LowestNewPrice`, `ListPrice`, first offer, `LowestUsedPrice`. Added support for the display of an artist name. Added rating stars. Fixed EAN numbers with a dash. Fixed cutting of titles after a period. Changed CSS and XSL files.  
* 1.1.3: Changed `amtap-admin.css` and `amtap-admin.inc.php` to make the admin interface look prettier with WordPress 2.5.
* 1.1.2: Fixed a bug in `amtap.php` when there are no other tags but default tags.
* 1.1.1: Fixed the sort order of inline items, a bug for returning an error message when the cache file is not writable, and added Amazon’s limit of 10 items per request.
* 1.1.0: Fixed the display of inline tags on the home page. Improved regular expression for filtering inline tags.
* 1.0.6: Fixed a bug introduced through the new function when there were no items to be displayed in the sidebar.
* 1.0.5: Added an option for default items on every page. Changed `amtap-html-sidebar.xsl` to sort items in the order of the request.
* 1.0.4: Bugfix for replacement of dollar characters in content. Also content items are now cached separately.
* 1.0.3: Fixed the display of inline tags on category pages.
* 1.0.2: Changed the plugin path from `amtap` to `amazon-machine-tags` for consistency with the file structure in the zipped file.
* 1.0.1: Bugfix for native WordPress tags.