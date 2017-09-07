=== Keyword to URL ===
Contributors: MBird
Tags: keywords, links
Requires at least: 3.2
Tested up to: 4.3
Stable tag: 1.5
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.en.html

Keyword to URL allows admins to create and save a list of keywords paired with URLs. Any keyword appearing in a post is then shown as a link.

== Description ==

Note:

The plugin is no longer maintained and should be considered deprecated.

About:

The plugin allows administrators to build and save a table of keywords and URLs. Once saved any post (or page) that has the keyword will now show that word (or phrase) as a link.

You can import and export a text file that is a comma-separated list of keyword-URL pairs.

You can choose to link every occurrence of the keyword in a post or just the first occurrence.

The plugin also recognizes e-mail addresses as links.

Anytime a keyword is already linked in a post it will be left unaffected by the plugin (that is, you can override the plugin's links by just adding your own anytime).

You can signal a post to not use the plugin's linking by adding a comment anywhere in the post:
&lt;!-- no keywords --&gt;

Tips:

If you have both a keyword and a keyword phrase that uses that keyword, the longer of the two will be given priority.
Example:
You have keywords for world and world record.
Your content is: We saw another world record heat wave today. This was not a great surprise to the world at large as many people around the world have reported warmer than usual temperatures this week.

The plugin will link all occurrences of the full phrase world record first. It will then link any singular occurrences of world. Therefore, the above content would contain a total of three links, one for the occurrence of world record in the first sentence and one for each occurrence of world in the second sentence.

Using a special comment to tell the plugin not to act on this post:
If you add the comment &lt;!-- no keywords --&gt; anywhere in your post the plugin will not act on this post. It may be better to add the comment at the end of the post because if add it at the top WordPress may add a linefeed to you post so you will see a space

Adding your own links in a post or page overrides the plugin:
The plugin only adds links to keywords that are not already linked.
Example:
You have a keyword for contact us.
Your content is: Please contact us for more info. You can also &lt;a href="#"&gt;contact us by phone&lt;/a&gt;.
The plugin will only link the first contact us as the second one is already linked in the post.

Dealing with keywords that have special or dis-allowed characters:
The plugin does not allow keywords with single or double quotes or with ampersands. If you have words in your posts or pages that conatin these characters and need links you can just manually add them yourself. You can always manually add a link anwhere on your site. The plugin will not affect your existing links (see notes above).

== Installation ==

1. Unzip the plugin to the /wp-content/plugins/directory.
2. Activate the plugin through the Plugins menu in WordPress.

== Changelog ==

= 1.5 =
* Final version with deprecation notice

= 1.4 =
* Minor updates and consoloidation of older releases

= 1.3 =
* Added Import/Export capabilities
* Change the way .js files are enqueued 
* Converted keyword-to-url.js to use JQuery
* Minor changes to the User Interface
* Minor changes to the PHP code

= 1.2 =
* Updated the code to disregard keywords in h1-h6 selectors.

= 1.1 =
* Improved the scan algorithm.
* Improved the uninstaller.

= 1.0 =
* The initial release.
