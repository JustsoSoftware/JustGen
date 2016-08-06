# JustGen

Simple HTML page generator module.

HTML pages are generated on-the-fly on base of Smarty templates with texts from JustTexts package and placed in the
file system to be retrieved directly later. When the template or text is changed (aka content management), it is
sufficient to delete the generated HTML files to have then re-generated again later.

The package doesn't try to be a content management system, but a smart tool to help people have more control with html
pages and the content without the need to use a 'real' CMS with all it's superpowers. Instead, you can create small and
really fast web pages in multiple languages.

The module utilizes Apache rewriting, the JustAPI and the JustTexts package.

## Installation

### Composer
  composer require justso/justgen:1.*

### git
  git clone git://github.com/JustsoSoftware/JustGen.git vendor/justso/justgen
  
## Setup

Checkout in vendor/justso/justgen and append a line

```
  "justgen/*":   "file:vendor/justso/justgen/services.json"
```

to your config.json file (see JustAPI package).

To make the automatic page generation work, you need to extend your Apache configuration like this:

```
  <Directory /var/www/htdocs>
    ...
    RewriteEngine On
    RewriteCond %{SCRIPT_FILENAME} !-f
    RewriteCond %{SCRIPT_FILENAME} !-d
    RewriteRule ^(.*)$ /api/justgen/ [L,PT,QSA]

    # Handle missing DirectoryIndex file via JustGen - the rewrite rule wouldn't work here
    ErrorDocument 403 /api/justgen/
  </Directory>
```

After reloading your Apache, pages can be generated.

To make JustTexts frontend work with JustGen, you need to overwrite two entries in your dependencies.php file:

```
  '\justso\justtexts\Text'    => '\justso\justgen\model\Text',
  '\justso\justtexts\Page'    => '\justso\justgen\model\Page',
```

## Templates

Templates are stored in a /templates folder and are filled by the generator with help from Smarty http://www.smarty.net

templates are selected according to a requested page via rules defined in the config.json file.
In the section "pages" you can define which page name uses which template file, for example:

```
{
  ...
  "pages": {
    "my-page": "ExampleTemplate"
  }
  ...
}
```

The generator checks if there is a page rule defining a template, and then uses the template to generate
the actual HTMl. It is sended then back to the requesting browser and additionally used to generate a file
in the htdocs folder, so that consecutive accesses find this file and the content need not to be generated
again.

So, how about dynamic content? Dynamic content should be handled differently, for example by loading it
via AJAX requests and not be used in a fixed, generated way.

## Redirects

Sometimes, you need to redirect URLs to new ones. Therefore, you can configure the generator to map an old
URL to a new one. This is done by adding a section "redirects" to config.json like this:

```
{
  ...
  "redirects": {
    "old-page": "new-page"
  }
  ...
}
```

## Support & More

If you need support, please contact us: http://justso.de
