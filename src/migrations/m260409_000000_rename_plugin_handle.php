<?php

namespace wabisoft\bonsaitwig\migrations;

use craft\db\Migration;

class m260409_000000_rename_plugin_handle extends Migration
{
    public function safeUp(): bool
    {
        $this->update('{{%plugins}}', [
            'handle' => 'bonsai-twig',
        ], [
            'handle' => '_bonsai-twig',
        ]);

        return true;
    }

    public function safeDown(): bool
    {
        $this->update('{{%plugins}}', [
            'handle' => '_bonsai-twig',
        ], [
            'handle' => 'bonsai-twig',
        ]);

        return true;
    }
}
