moodle-mod_panopto
==================

This is Panopto resource module plugin developed by Lancaster University to
simplify using Panopto video recordings in Moodle courses. This plugin works
differently to official [Panopto
block](https://moodle.org/plugins/block_panopto) plugin. First, it does not do
rolling sync enrolled users with the Folder on Panopto, instead it grants
access directly to video on demand. This eliminates the need to place
recordings at certain folder in Panopto to make it available in Moodle and
respects all Moodle resource availability features, such as group restriction
or activity completion. Choosing a video is implemented as [repository
plugin](https://github.com/lucisgit/moodle-repository_panopto), which makes
navigation and selecting the video more convenient for teacher.

In short, the plugin lets Moodle decide if user is allowed to access video
resource, and does background work to provide access to recording on demand.
This approach is somewhat simpler than used in official Panopto Block plugin
and still secure, but might be not suitable for everyone.

<img src="https://moodle.org/pluginfile.php/50/local_plugins/plugin_screenshots/2151/panopto_pick_video_desc.png" width="600px" />

### Features

* This is resource module plugin, not a block, e.g it supports separate groups, availability, completion features.
* Moodle resource module permission is the definitive source of authorisation, e.g. separate groups, availability features, category enrolments are respected.
* Repository plugin makes navigation, searching and choosing the right video easier.
* No need to maintain a separate folder per course in Panopto and sync enrolled users for folder access.
* Any video can be added to course irrespective of its location in Panopto folder structure as long as teacher who adds it has editing or publishing rights.
* Moving video to different folder will not break access, access is granted to video, not to folder.
* Same video can be used in different courses, no need to duplicate it and place at different folders.
* Does not clash with manual permissions allocation via Panopto interface, you can grant user access manually to folder or video if required.

### Use-case and a word of caution

At Lancaster University, Panopto is used for automated lecture recordings,
but Panopto is not the main point for accessing recorded videos by
students. In other words, students are always accessing video recordings
through Moodle.  If you are using Panopto as main video hosting platform
and you expect students to see folders that are matching Moodle courses
(like when Panopto Block is used) this plugin will not provide that
functionality.

What happens under the bonnet
-----------------------------

- When teacher adds video to Moodle (creates resource module in the course
section), plugin creates Panopto external group named after unique
`coursemodule` and links this group to particular video.
- When user clicks on
activity, the user is being added to this unique group temporarily, so that
she has access to view it, and then redirected to panopto video page for
viewing.
- After some timeout window (can be configured in plugin settings) the user
is removed from external group automatically (using regular task), to make
sure no access permissions remain in place.
- When user attempts to view activity again,
the system will verify if access is still in place and either will update
access window timestamp (to reset timeout) or will add user to external group
again if access has been revoked already.
- If activity is removed, its unique external group is deleted.
- If the same video added to different course, a new unique external group will be
created and linked to video.
- While user access is granted on demand for
short time and decay after timeout, all activity that user does on Panopto
side is preserved, such as viewing statistics or comments on the video.

Installation
------------

This plugin requires Panopto [repository
plugin](https://github.com/lucisgit/moodle-repository_panopto) to be installed and
configured, it is using it for navigation through directory tree on Panopto
side and other API calls.

The actual installation is quite usual, just place plugin content at
`./mod/panopto` directory and go though installation in Moodle admin interface.

Make sure that [block_panopto](https://moodle.org/plugins/block_panopto) is
not installed in your system, in fact it may work together, but will cause
a lot of confusion due to the differences in access rights allocation.

Configuration
-------------

Global plugin configuration allows admin to set timeout window after which
temporary viewing permissions will be removed (see "[What happens under the
bonnet](#what-happens-under-the-bonnet)" above).

Also make sure that [repository
plugin](https://github.com/lucisgit/moodle-repository_panopto) is [configured](https://github.com/lucisgit/moodle-repository_panopto#configuration) to use your Panopto site.

Panopto API library
-------------------

Plugin is using
[php-panopto-api](https://github.com/lucisgit/php-panopto-api) PHP library
which covers full Panopto API functionality and has been developed specifically
for this plugin.
