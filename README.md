moodle-mod_panopto
==================

This is Panopto resource module plugin developed by Lancaster University to
simplify using Panopto video recordings in Moodle courses. This plugin
works differently to "offical" [Panopto
block](https://moodle.org/plugins/block_panopto) plugin. First, it does not
sync enrolled users with the Folder on Panopto, instead it grants access
directly to video, this eliminates the need to place recordings at certain
folder in Panopto to make it available in Moodle. Second, it respects all
Moodle resource availability features, such as group restiction or activity
completion. Finally, choosing a video is implemented as [repository
plugin](https://github.com/lucisgit/moodle-repository_panopto),
which makes navigation and chosing the right video much easier for teacher.

In short, the plugin lets Moodle to decide if user is allowed to access
video resource, and doing background work to provide access to recording on
demand. This approach is somewhat simpler than used in official Panopto
Block plugin and still secure, but might be not suitable for everyone.

### Features

* This is resource module plugin, not a block, e.g it supports separate
groups, availability, completion features.
* No need to maintain a separate folder per course in Panopto and sync
enrolled users for folder access.
* Any video can be added to course irrespective of its location in Panopto
folder stucture as long as teacher who adding it got editing or publishing
rights.
* Moving video to different folder will not break access, access is granted
to video, not to folder.
* Same video can be used in different courses, no need to duplicate it and place at different folders.
* Moodle resource module permission is the definitive source of
authorisation, e.g. separate groups, avaiaibility featurs, category enrolments are respected.
* Repository plugin makes navigation, searching and chosing the right video easier.
* Does not clash with manual permissions allocation via Panopto interface,
you can grant user access manually to folder or video if required.

### Use-case and a word of caution

In Lancaster University, Panopto is used for automated lecture recordings,
but Panopto is not the main point for accessing recorded videos by
students. In other words, students are always accessing video recordings
through Moodle.  If you are using Panopto as main video hosting platform
and you expect students to see folders that are matching Moodle courses
(like when Panopto Block is used) this plugin will not provide that
functionlity.

What happens under the bonnet
-----------------------------

When teacher adds video to Moodle (creates resource module in the course
section), plugin creates Panopto external group named after unique
`coursemodule` and link this group to particular video. When user clicks on
activity, the user is being added to this unique group temporally, so that
she has access to view it, and then redirected to panopto video page for
viewing. After some timeout (can be configured in plugin settings) the user
is removed from external group automatically (using regular task), to make
sure there is no access permission remain in place irrespective to possible
permission changes on Moodle. When user attempts to view activity again,
the system will verify if access is still in place and either will update
access timestamp to reset access timeout or will add user to external group
again if access has been seized already.

If activity is removed, its unique external group is deleted. If the same
video added to different course, the new unique external group will be
created and linked to video. While user access is granted on demand for
short time and decay after timeout, all activity that user does on Panopto
side is preserved, such as viewing statistics or comments on the video.

Installation
------------

This plugin requires Panopto [repository
plugin](https://github.com/lucisgit/moodle-repository_panopto) to be installed and
configured, it is using it for navigation through directory tree on Panopto
side.

The actual installation is quite usual, just place plugin content at
`./mod/panopto` directory and go though installation in Moodle admin interface.

Make sure that [block_panopto](https://moodle.org/plugins/block_panopto) is
not installed in your system, in fact it may work together, but will cause
a lot of confusion due to the differences in access rights allocation.

Configuration
-------------

Global plugin configuration allows admin to set timeout after which
temporal viewing permissions will be removed (see "[What happens under the
bonnet](#what-happens-under-the-bonnet)" above).

Panopto API library
-------------------

Plugin is using
[php-panopto-api](https://github.com/lucisgit/php-panopto-api) PHP library
which covers full Panopto API functionality and has been developed specifically
for this plugin.
