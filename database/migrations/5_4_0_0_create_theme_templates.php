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
        $convertToTemplateIds = [];
        $templates = DB::table('templates')->get();
        foreach ($templates as $template) {
            if (array_key_exists($template->template, $templatesFound)) {
                if (!array_key_exists($template->template, $templatesFound)) {
                    $convertToTemplateIds[$templatesFound[$template->template]] = [];
                }
                $convertToTemplateIds[$templatesFound[$template->template]][] = $template->id;
                $deleteTemplateIds[] = $template->id;
                $template->id = $templatesFound[$template->template];

            } else {
                $templatesFound[$template->template] = $template->id;
            }
            $template->template_id = $template->id;
            $themeTemplates[$template->theme_id . '.' . $template->template] = array_diff_key((array) $template, array_fill_keys(['id', 'template'], ''));
        }

        $convertTemplateIds = [];
        foreach ($convertToTemplateIds as $mainTemplateId => $convertIds) {
            DB::table('template_blocks')->whereIn('template_id', $convertIds)->update(['template_id' => $mainTemplateId]);
            $convertTemplateIds = $convertTemplateIds + array_fill_keys($convertIds, $mainTemplateId);

        }

        $themeBlocks = DB::table('theme_blocks')->get();
        foreach ($themeBlocks as $themeBlock) {
            if ($themeBlock->exclude_templates) {
                $templates = explode(',', $themeBlock->exclude_templates);
                $templatesBase = array_combine($templates, $templates);
                $templates = array_unique(array_intersect_key($convertTemplateIds, $templatesBase) + $templatesBase);
                DB::table('theme_blocks')->where('id', '=', $themeBlock->id)->update(['exclude_templates' => implode(',', $templates)]);
            }
        }


        DB::table('theme_templates')->insert($themeTemplates);
        DB::table('templates')->whereIn('id', $deleteTemplateIds)->delete();
        Schema::table('templates', function(Blueprint $table) {
            $table->dropColumn('theme_id');
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
