# Theme Css Variables
⚠️ This package is experimental! Use at your own risk!

## What is does...
This [Neos](https://neos.io) package provides a backend module giving users with access to the module
to create a custom stylesheet with CSS custom properties (a.k.a. CSS-variables) based on the 
custom properties that are found in the Public folder of the site.

The custom stylesheet is then loaded after the defined stylesheets via Fusion (so it is configurable if loading doesn't 
work out of the box). 

The result? A custom version of the website 😉.

The application tries to make an educated guess on the type of variable (color, font, breakpoint).
Each of those can be enabled via the `Settings.yaml`. (See below).

![](Resources/Private/Documentation/Images/Module.png)

### What happens in the background?
The module reads all CSS files in the `Resources/Public` folders of your packages (minified or not) and extracts the custom properties (`--name: value`).
If multiple files are found (for example when using a bootstrap based theme) the array of variables
get merged where the top level file is considered to be the most relevant one. 

When you change the values, the overrides are stored in `Data/Persistent/Theme/CssVariables/<site>/custom.css`.
Fusion renders that file inline as a `<style>` tag in the page head, so nothing is published to `Web/_Resources`
and the overrides survive deployments that share `Data/Persistent`. Saving or restoring flushes the content cache.

It is loaded as well, if it exists, and is merged as last so the module shows the active settings
for the variables.

Optionally you can restore the defaults by removing the file.

## Installation

Install by running:
```$bash
composer require theme/cssvariables
```

## Configuration

### Settings
| Setting  | Type | Default value  | Explanation  |
|---|---|---|---|
|  stylesheetName | `string` | `custom.css`  | The name of the file to be generated.  |
|  types | `array` | `color` | Supported are: `color`,`font`,`breakpoint`,`other`. Where `other` means every variable we could not sensibly map to one of the others. |

### Fusion
The overrides are rendered as `<style data-theme-cssvariables>` in `Neos.Neos:Page.head.cssVariables`,
with `@position = 'after stylesheets'`. If your Neos Page setup is different, make sure it is rendered after your site's CSS files.

The Eel helper `CssVariables.customCss(site)` returns the stored CSS, if you want to render it elsewhere.

## Compatibility

| Version | Neos |
| --- | --- |
| 0.3.x | 8.3, 8.4, 9.0, 9.1, 9.2 |
| 0.2.x | not installable on current Neos 8/9 |

### Access to the module
Our default policy is set for `Neos Administrators` only. However feel free to copy-paste the 
contents to your site package Policy.yaml and change it to whatever role you deem necessary.

Please be aware of one thing though: if more then one user can change the variables they need not be surprised
when some of the other users might pick a color they think is 💩!

## FAQ

### My css doesn't use CSS custom properties, can I still use this package?
Lot's of sites are build using SCSS and the Sass variables (i.e. `$primary-color`). 
When compiling the CSS the variable is substituted by it's value, and not like with the 
css custom properties as a reference. The result is hardcoded values and renders changing
the CSS custom properties kinda useless...

**But...** before we forget about the fun: there is an experimental feature that reads
your compiled css file and looks for all values that could be substituted by a reference.
You can try it out by running `./flow theme:replaceColorVariables`. 

As you guessed by the name, we currently only support substitution of variables that are considered
to be color references. (Mainly for stability reasons).

A backup of your original CSS file is created, so feel free to give it a try!

### Something is not working as expected, what should I do?
If you run into any issues, feel free to create a pull request or raise an issue. Please be as specific as possible and then a litte more... 😉
