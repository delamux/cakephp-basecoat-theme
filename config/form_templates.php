<?php
/**
 * Basecoat templates for the core FormHelper (templates only).
 *
 * Prefer the plugin FormHelper, which also adds the Basecoat classes to every widget:
 *   $this->addHelper('Form', ['className' => 'BasecoatTheme.Form']);
 *
 * If you want to keep the core FormHelper and only change the wrappers:
 *   $this->addHelper('Form', ['templates' => 'BasecoatTheme.form_templates']);
 * and add the classes yourself (`'class' => 'input'`, `'class' => 'select'`...).
 */
return [
    'inputContainer' => '<div class="field{{required}}" role="group" data-type="{{type}}">{{content}}</div>',
    'inputContainerError' => '<div class="field{{required}}" role="group" data-type="{{type}}" data-invalid="true">{{content}}{{error}}</div>',
    'checkboxContainer' => '<div class="field{{required}}" role="group" data-type="{{type}}" data-orientation="horizontal">{{content}}</div>',
    'checkboxContainerError' => '<div class="field{{required}}" role="group" data-type="{{type}}" data-orientation="horizontal" data-invalid="true">{{content}}{{error}}</div>',
    'error' => '<p class="text-sm" role="alert" id="{{id}}">{{content}}</p>',
    'errorList' => '<ul>{{content}}</ul>',
    'errorItem' => '<li>{{text}}</li>',
    'fieldset' => '<fieldset class="fieldset"{{attrs}}>{{content}}</fieldset>',
    'legend' => '<legend>{{text}}</legend>',
    'multicheckboxTitle' => '<legend>{{text}}</legend>',
    'multicheckboxWrapper' => '<fieldset class="fieldset"{{attrs}}>{{content}}</fieldset>',
    // {{input}} is empty when the input is nested in {{label}} (core default `nestedCheckboxAndRadio`)
    'checkboxWrapper' => '<div class="flex items-center gap-3">{{input}}{{label}}</div>',
    'radioWrapper' => '<div class="flex items-center gap-3">{{input}}{{label}}</div>',
    'submitContainer' => '<div class="flex gap-2">{{content}}</div>',
];
