<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateThemeTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('theme_templates', function (Blueprint $table) {
            $table->create();
            $table->increments('id');
            $table->integer('theme_id');
            $table->integer('template_id');
            $table->string('label')->nullable();
            $table->integer('child_template')->nullable();
            $table->integer('hidden')->nullable();
            $table->timestamps();
        });

        $themeTemplates = [];
        $deleteTemplateIds = [];
        $templatesFound = [];
        $templates = DB::table('templates')->get();
        foreach ($templates as $template) {
            if (array_key_exists($template->template, $templatesFound)) {
                $deleteTemplateIds[] = $template->id;
            } else {
                $templatesFound[$template->template] = $template;
            }
            $themeTemplate = array_diff_key((array) $template, array_fill_keys(['template'], ''));
            foreach (['label', 'child_template', 'hidden'] as $attribute) {
                if ($templatesFound[$template->template]->$attribute == $themeTemplate[$attribute]) {
                    $themeTemplate[$attribute] = null;
                }
            }
            $themeTemplate['template_id'] = $templatesFound[$template->template]->id;
            $themeTemplates[] = $themeTemplate;
        }

        Schema::rename('template_blocks', 'theme_template_blocks');
        DB::table('theme_templates')->insert($themeTemplates);
        DB::table('templates')->whereIn('id', $deleteTemplateIds)->delete();
        Schema::table('templates', function(Blueprint $table) {
            $table->dropColumn('theme_id');
        });
        Schema::table('theme_template_blocks', function(Blueprint $table) {
            $table->renameColumn('template_id', 'theme_template_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }

}
