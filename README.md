# TYPO3 Extension: Secured Files
  * Description: Simple protection of files, allows to protect file-mounts with fe_groups. Requires some .htaccess settings.
  * Extension key: koning_secured_files


Howto
-----

Rewrite all requests to files in this directory to koning_secured_files via:
```apacheconf
    # Example for /fileadmin/protected/<files>
    RewriteRule ^fileadmin/protected/.*$ /index.php?eID=koning_secured_files&file=%{REQUEST_URI} [NC,L]
```
Use this in your htaccess to rewrite requests of files to this extension.
