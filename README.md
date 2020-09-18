# Search plugin for CakePHP

[![Build Status](https://travis-ci.org/QoboLtd/cakephp-search.svg?branch=master)](https://travis-ci.org/QoboLtd/cakephp-search)
[![Latest Stable Version](https://poser.pugx.org/qobo/cakephp-search/v/stable)](https://packagist.org/packages/qobo/cakephp-search)
[![Total Downloads](https://poser.pugx.org/qobo/cakephp-search/downloads)](https://packagist.org/packages/qobo/cakephp-search)
[![Latest Unstable Version](https://poser.pugx.org/qobo/cakephp-search/v/unstable)](https://packagist.org/packages/qobo/cakephp-search)
[![License](https://poser.pugx.org/qobo/cakephp-search/license)](https://packagist.org/packages/qobo/cakephp-search)
[![codecov](https://codecov.io/gh/QoboLtd/cakephp-search/branch/master/graph/badge.svg)](https://codecov.io/gh/QoboLtd/cakephp-search)
[![BCH compliance](https://bettercodehub.com/edge/badge/QoboLtd/cakephp-search?branch=master)](https://bettercodehub.com/)

## About

CakePHP 3+ plugin for flexible and powerful searching of structured data.

This plugin is developed by [Qobo](https://www.qobo.biz) for [Qobrix](https://qobrix.com).  It can be used as standalone CakePHP plugin, or as part of the [project-template-cakephp](https://github.com/QoboLtd/project-template-cakephp) installation.

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

## Setup

Install plugin
```
composer require qobo/cakephp-search
```

Load plugin
```
bin/cake plugin load Search
```

Load required plugin(s)
```
bin/cake plugin load Muffin/Trash
```

Load Component

In your AppController add the following:

```php
    $this->loadComponent('Qobo/Search.Searchable');
```
