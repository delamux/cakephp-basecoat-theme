<?php
/**
 * @title Capture mode
 * @description start() and end() wrap any template code in a card, handy for large bodies.
 */
echo $this->Card->start(['title' => 'Team members', 'description' => 'People with access to this project.']);
?>
<ul class="grid gap-2 text-sm">
    <li>Elena Sarto - Owner</li>
    <li>Kofi Mensah - Editor</li>
    <li>Ines Duarte - Viewer</li>
</ul>
<?php
echo $this->Card->end(['footer' => $this->Button->render('Invite member', ['icon' => 'plus'])]);
