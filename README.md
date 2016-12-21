# EasyStaging-for-Joomla
EasyStaging is a Joomla! 2.5.4+ and Joomla 3.3+ component that simplifies the process of copying changes from a private "staging" version of a website to a "live" or public webserver. 

## How does it work?
EasyStaging works by copying your website from a "staging" location to a "live" location.

EasyStaging uses two basic mechanisms to perform the copy.

 1. File copy using rsync to the "live" site.
 2. MySQL table exports to the dababase of the "live" site.
 
### File copying

EasyStaging "copies" the changed files from your "staging" location to the "live" location using a process called rsync.
[You can read about rsync here](http://www.samba.org/ftp/rsync/rsync.html).

EasyStaging allows you to specify which files/directories are excluded from the copy process. The default settings don't copy the staging sites configuration.php file, cache, tmp or log directories.

### Database copying

EasyStaging will copy tables from your local "staging" websites to the remote "live" websites database. (By default EasyStaging will not copy the #__session table to prevent users from being disconnected.)

### Staging Plans

EasyStaging works around the concept of "Plans". Each Plan can be configured to copy specific tables from the staging database, specific directories or a combination of both to the "live" website.

Plans can also be restrcted so that a user can only "run" a Plan in a specific Joomla! group. This could be handy if a website author needs to push new PDF's or images to your live website but you don't want anything else transferred, you can create a plan just for that user that copies just their directory of files and nothing else.

## Who should use it?

EasyStaging is meant to make the life of website developers or other suitable technically skilled people easier. If nothing you've read so far is unfamiliar then its probably a good idea to read further. If EasyStaging doesn't sound dangerous to you then you should probably stop reading and find another product.

In the right hands EasyStaging can be a powerful and time saving tool, but in the wrong hands a badly configured staging Plan could wipe out your live website.

## Minimum Requirements

Apart from the requirements listed in the table below, the Apache user the runs PHP on your staging website should be configured to allow shell_exec(), proc_open() and proc_close() calls. The Apache user should also be allowed to run at, i.e. it should not be in the at.deny file.

 
| 	      | Minimum	Recommended |
|:--------|--------------------:|
| Joomla! | 3.6.x               |
| MySQL   | 5.1+                |
| PHP     | 5.6+                |
| rsync | 3.0.6+ |
