<?php
/**
 * @title Basic form
 * @description Core FormHelper API with Basecoat markup: fields, select, textarea and a submit button.
 */
echo $this->Form->create(null, ['url' => '/contact']);
echo $this->Form->control('full_name', ['label' => 'Full name', 'placeholder' => 'Noor Haddad']);
echo $this->Form->control('email', ['type' => 'email', 'label' => 'Email']);
echo $this->Form->control('topic', ['label' => 'Topic', 'options' => ['sales' => 'Sales', 'support' => 'Support', 'other' => 'Other']]);
echo $this->Form->control('message', ['type' => 'textarea', 'label' => 'Message']);
echo $this->Form->button('Send message');
echo $this->Form->end();
