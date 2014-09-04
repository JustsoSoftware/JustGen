# JustGen

Simple HTML page generator module.

HTML pages are generated on-the-fly on base of Smarty templates with texts from JustTexts package and placed in the
file system to be retrieved directly later. When the template or text is changed (aka content management), it is
sufficient to delete the generated HTML files to have then re-generated again later.

The package doesn't try to be a content management system, but a smart tool to help people have more control with html
pages and the content without the need to use a 'real' CMS with all it's superpowers. Instead, you can create small and
really fast web pages in multiple languages.

The module utilizes Apache rewriting, the JustAPI and the JustTexts package.

## Setup

Checkout in vendor/justso/justgen and append a line

```
  "justgen\/*":   "file:vendor\/justso\/justgen\/services.json"
```

to your config.json file (see JustAPI package).

To make the automatic page generation work, you need to extend your Apache configuration like this:

```
  <Directory /var/www/htdocs>
    ...
    RewriteEngine On
    RewriteCond %{SCRIPT_FILENAME} !-f
    RewriteCond %{SCRIPT_FILENAME} !-d
    RewriteRule ^(.*)$ /api/justgen/ [L]

    # Handle missing DirectoryIndex file via JustGen - the rewrite rule wouldn't work here
    ErrorDocument 403 /api/justgen/
  </Directory>
```

After reloading your Apache, it should work.

## Templates

Templates are stored in a /template folder and are filled by the generator with help from Smarty http://www.smarty.net

## Support & More

If you need support, please contact us: http://justso.de