Moodle Newsletter Module
============================
A native newsletter module for creating, managing and sending newsletters.

Required version of Moodle
==========================
This version works with Moodle release 2.7 and above.

Free Software
=============
The newsletter module is 'free' software under the terms of the GNU GPLv3 License.

It can be obtained for free from:
https://moodle.org/plugins/view.php?plugin=mod_newsletter
and
https://github.com/dasistwas/moodle-mod_newsletter

You have all the rights granted to you by the GPLv3 license.  If you are unsure about anything, then the
FAQ - http://www.gnu.org/licenses/gpl-faq.html - is a good place to look.

If you reuse any of the code then I kindly ask that you make reference to the original author.

If you make improvements or bug fixes then I would appreciate if you would send them back to me by forking from
https://github.com/dasistwas/moodle-mod_newsletter and doing a 'Pull Request' so that the rest of the
Moodle community benefits.

Supporting the development of the newsletter module
===========================
If you find the module useful, then consider contracting my company. 

Contact info@edulabs.org for details or visit http://www.edulabs.org

Installation
============
1. Ensure you have the version of Moodle as stated above in 'Required version of Moodle'.  This is essential as the
   format relies on underlying core code that is out of my control.
2. Put Moodle in 'Maintenance Mode' (docs.moodle.org/en/admin/setting/maintenancemode) so that there are no
   users using it bar you as the administrator - if you have not already done so.
3. Copy 'newsletter' to '/mod/' if you have not already done so.
4. Go back in as an administrator and follow standard the 'plugin' update notification.  If needed, go to
   'Site administration' -> 'Notifications' if this does not happen.
5. Put Moodle out of Maintenance Mode.
6. You may need to check that the permissions within the 'newsletter' folder are 755 for folders and 644 for files.

Uninstallation
==============
1. Put Moodle in 'Maintenance Mode' so that there are no users using it bar you as the administrator.
2. In the block Administration go to Site administration -> Plugins -> Overview and click the uninstall link.
3. In '/mod/' remove the folder 'newsletter'.
4. Put Moodle out of Maintenance Mode.

Upgrade Instructions
====================
1. Ensure you have the version of Moodle as stated above in 'Required version of Moodle'.  This is essential as the
   format relies on underlying core code that is out of my control.
2. Put Moodle in 'Maintenance Mode' so that there are no users using it bar you as the administrator.
3. In '/mod/' move old 'newsletter' directory to a backup folder outside of Moodle.
4. Copy new 'newsletter' to '/mod/'.
5. Go back in as an administrator and follow standard the 'plugin' update notification.  If needed, go to
   'Site administration' -> 'Notifications' if this does not happen.
6. If automatic 'Purge all caches' appears not to work by lack of display etc. then perform a manual 'Purge all caches'
   under 'Home -> Site administration -> Development -> Purge all caches'.
7. Put Moodle out of Maintenance Mode.