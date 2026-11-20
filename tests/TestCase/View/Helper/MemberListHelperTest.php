<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\MemberListHelper;

class MemberListHelperTest extends HelperTestCase
{
    public function testRolesSelect(): void
    {
        $html = (new MemberListHelper($this->View))->render(
            [['id' => 7, 'name' => 'Ana Ruiz', 'email' => 'ana@example.com', 'role' => 'editor'], ['name' => 'Bo Lin']],
            ['roles' => ['owner' => 'Owner', 'editor' => 'Editor', 'viewer' => 'Viewer']],
        );
        $this->assertStringContainsString('<ul role="list"', $html);
        $this->assertStringContainsString('name="roles[7]"', $html);
        $this->assertStringContainsString('name="roles[1]"', $html);
        $this->assertStringContainsString('class="select"', $html);
        $this->assertStringContainsString('aria-label="Role for Ana Ruiz"', $html);
        $this->assertStringContainsString('<option value="editor" selected="selected">Editor</option>', $html);
        $this->assertStringContainsString('ana@example.com', $html);
    }

    public function testReadonlyBadge(): void
    {
        $html = (new MemberListHelper($this->View))->render(
            [['name' => 'Ana Ruiz', 'role' => 'editor']],
            ['roles' => ['editor' => 'Editor'], 'readonly' => true],
        );
        $this->assertStringContainsString('<span class="badge"', $html);
        $this->assertStringContainsString('Editor</span>', $html);
        $this->assertStringNotContainsString('<select', $html);
    }

    public function testEscapes(): void
    {
        $html = (new MemberListHelper($this->View))->render(
            [['name' => '<script>x</script>', 'email' => '<b>e</b>', 'role' => 'a"b']],
            ['roles' => ['a"b' => '<i>r</i>']],
        );
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<b>e', $html);
        $this->assertStringNotContainsString('<i>r', $html);
    }

    public function testEmpty(): void
    {
        $helper = new MemberListHelper($this->View);
        $this->assertStringContainsString('No members yet.', $helper->render([]));
        $this->assertStringContainsString('Nobody', $helper->render([], ['empty' => 'Nobody']));
        $this->assertStringNotContainsString('<li', $helper->render([]));
    }

    public function testEscapeOptionIsCastToBool(): void
    {
        $html = (new MemberListHelper($this->View))->render(
            [['name' => '<b>Kofi</b>', 'email' => 'kofi@example.com', 'role' => 'member']],
            ['roles' => ['member' => 'Member'], 'escape' => 0],
        );
        $this->assertStringContainsString('<b>Kofi</b>', $html);
        $this->assertStringNotContainsString('escape=', $html);
    }

    public function testAvatarInitialsAreHiddenNextToTheName(): void
    {
        $html = (new MemberListHelper($this->View))->render([['name' => 'Noor Haddad']]);
        $this->assertStringContainsString('<span aria-hidden="true">NH</span>', $html);
        $this->assertStringContainsString('Noor Haddad', $html);
    }
}
