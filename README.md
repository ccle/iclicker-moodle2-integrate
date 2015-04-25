This is the Moodle i>clicker integrate plugin for Moodle 2+ which integrates Moodle 2+ with iClicker (http://www.iclicker.com/dnn/)
The plugin allows students to register their clickers with the Moodle 2+ installation. It provides
an adminstrative interface for managing the registrations for Moodle admins. Instructors are provided
with a reporting view which allows them to view a listing of the students in their courses which have
and have not registered clickers. The plugin also provides integration with the i>clicker and i>grader
desktop applications and allows direct grade import and export from the Moodle course gradebook.

Download the current release of the plugin from the moodle plugins directory:
[https://moodle.org/plugins/view.php?plugin=block_iclicker]

The 1.9 version is available in [https://github.com/azeckoski/iclicker-moodle-integrate]

[https://github.com/azeckoski/iclicker-moodle2-integrate/blob/master/README.txt README] (Installation and usage guide)


**NOTE about database errors in Moodle 2.8:**

If you are running Moodle 2.8 and installed a version of this plugin older than 26 April 2015 (1.8.1 or older) 
you may see an issue when viewing grades in the gradebook which produces an error like this:

    "Error reading from database"
    Debug info: Table 'moodle28.mdl_iclicker' doesn't exist
    SELECT c.* FROM mdl_iclicker instance
    JOIN mdl_course c ON c.id = instance.course
    WHERE instance.id = ?
    Error code: dmlreadexception
    ...

To fix this issue, upgrade your plugin to version 1.8.2 or newer and then run the following SQL 
(note that you may have to adjust the "mdl_grade_items" table name to match your local installation - depending on the configured database table prefix):

    update mdl_grade_items set itemtype = 'manual', itemmodule = NULL 
    where itemtype = "blocks" and itemmodule = "iclicker";

