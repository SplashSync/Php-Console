---
lang: en
permalink: docs/documentations
title: Build Documentations
---

## Use the Console to build Module's Documentations

Splash Php Console add a task to GrumPhp framework to automaticaly build module's documentstion.

This builder will create a Static Hml website using Jekyll builder.

To build documentations, Jekyll is required. all instructions to install it are at [jekyllrb.com](https://jekyllrb.com/)

## Builder Configuration

By default, documentation sources are located in 'src/Resources/docs'.

Her should be a minimal website configuration:

```yaml
# src/Resources/docs/_config.yml

# Title of the Bundle / Module
title: Splash Php Console
# Subtitle of the Site (Generaly DOC)
subtitle: DOC
# the subpath of your site, e.g. /blog
baseurl: "/Php-Console" 
# Description of the Bundle/Module
description:    "Php Console for All Splash Modules, Bundles & Connectors.
                Brings all usefull tools for developper & advanced Splash Sync Users.
                A simple and efficient CLI Console for Executing Test & Mass Actions."
# Package Code on Packagist
package:    "splash/console"
```

### Documentation Sections

Your documentations contents may be placed in different sections that will be automaticaly parsed by the builder.

* start/block-title: A quick start section block.
* docs/block-title: A general documentation section block.
* faq/block-title: An FAQ section block.

I.e. for a quick start paragraph, in English.

```markdown
---
lang: en
permalink: start/setup
title: Configure the Module
---
```

### Typography

Most part of basics Markdown syntax will be converted to html. 

More over, you can use few custom typo...

#### Alerts

Create nice alerts blocks by adding a div class.

```markdown
<div class="success">
	This is a nice alert block!!!
</div>
```

<div class="success">
	This is a nice alert Success block!!!
</div>

<div class="warning">
	This is a nice alert Warning block!!!
</div>

<div class="danger">
	This is a nice alert Danger block!!!
</div>

<div class="info">
	This is a nice alert Info block!!!
</div>



