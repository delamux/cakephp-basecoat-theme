<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\FormHelper;

class FormHelperTest extends HelperTestCase
{
    public function testControlsGetBasecoatMarkup(): void
    {
        $form = new FormHelper($this->View);
        $this->assertSame(
            '<div class="field text" role="group"><label for="title" class="label">Title</label><input type="text" name="title" id="title" class="input"></div>',
            $form->control('title'),
        );
        $this->assertStringContainsString('<textarea name="body" id="body" class="textarea" rows="5"></textarea>', $form->control('body', ['type' => 'textarea']));
        $this->assertStringContainsString('<select name="kind" id="kind" class="select">', $form->control('kind', ['options' => ['a' => 'A']]));
        $this->assertStringContainsString(
            '<div class="field checkbox" role="group" data-orientation="horizontal"><input type="hidden" name="ok" value="0"><label for="ok" class="label gap-3"><input type="checkbox" name="ok" value="1" id="ok" class="input">Ok</label></div>',
            $form->control('ok', ['type' => 'checkbox']),
        );
    }

    public function testUserClassesAreKept(): void
    {
        $form = new FormHelper($this->View);
        $this->assertStringContainsString('class="input h-8 w-16"', $form->text('target', ['class' => 'h-8 w-16']));
        $this->assertStringNotContainsString('class=', $form->hidden('id'));
    }

    public function testButtonsVariantsAndSubmit(): void
    {
        $form = new FormHelper($this->View, ['button' => ['size' => 'sm']]);
        $this->assertSame(
            '<button type="submit" data-variant="destructive" data-size="sm" class="btn">Delete</button>',
            $form->button('Delete', ['variant' => 'destructive']),
        );
        $this->assertSame(
            '<div class="flex gap-2"><input type="submit" data-variant="outline" data-size="sm" class="btn" value="Send"></div>',
            $form->submit('Send', ['variant' => 'outline']),
        );
    }

    public function testWidgetClassesAndOptOut(): void
    {
        $form = new FormHelper($this->View, ['widgetClasses' => ['textarea' => 'textarea min-h-32']]);
        $this->assertStringContainsString('class="textarea min-h-32"', $form->textarea('notes'));
        $this->assertStringNotContainsString('class=', $form->text('raw', ['basecoat' => false]));
    }

    public function testTemplatesFileForCoreHelper(): void
    {
        $core = new \Cake\View\Helper\FormHelper($this->View, ['templates' => 'BasecoatTheme.form_templates']);
        $this->assertSame('<div class="field {{type}}{{required}}" role="group">{{content}}</div>', $core->templater()->get('inputContainer'));
    }
}
