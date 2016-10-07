# JustGen

Simple HTML page generator module.

HTML pages are generated on-the-fly on base of Smarty templates with texts from JustTexts package and placed in the
file system to be retrieved directly later. When the template or text is changed (aka content management), it is
sufficient to delete the generated HTML files to have them re-generated again later.

The package doesn't try to be a content management system, but a smart tool to help people have more control with html
pages and the content without the need to use a 'real' CMS with all it's superpowers. Instead, you can create small and
really fast web pages in multiple languages.

## Installation

### Composer
  composer require justso/justgen

### git
  git clone git://github.com/JustsoSoftware/JustGen.git vendor/justso/justgen
  
## Setup

Checkout in vendor/justso/justgen and add the lines

```
  "/justtexts/page/*/text/*": "justso\\justgen\\TextService",
  "/justgen/flushcache":      "justso\\justgen\\FlushCache",
  "*":                        "file:vendor/justso/justgen/services.json"
```

to your `config.json` file section "services" (see JustAPI package). Make sure that the first precedes the entry
for "justtexts" so that the modified TextService class is used. It adds not yet defined text containers to the
JustTexts frontend when editing page content if they are used in the template.
The `*` rule should be the last of all your rules, and catches all unknown accesses and tries to find a page to
generate. The page generator itself sends an 404 error if no such page exists.

If you use Apache, your configuration should be like that:

```
  <Directory /var/www/htdocs>
    ...
    Redirect 301 /index /de/
    ErrorDocument 403 /api/justgen/
    ErrorDocument 404 /api/justgen/
  </Directory>
```

With NginX, make sure that 403 and 404 errors are routed to the FrontController:

```
    error_page 403 404 = /vendor/justso/justapi/FrontController.php;
```

To make JustTexts frontend work with JustGen, you need to overwrite an entry in your dependencies.php file:

```
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
