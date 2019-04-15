<?php
use CsvMigrations\FieldHandlers\FieldHandlerFactory;
use Qobo\Utils\Utility;

$fhf = new FieldHandlerFactory($this);
$templates = [];

foreach ($savedWidgetData as $key => $element) {
    if(array_key_exists("inputs", $element["data"])){
        $columns = [];
        $moduleName = $element['data']['model'];

        if (!empty($moduleName)) {
            $columns = Utility::getModelColumns($moduleName);
        }
        $element_id = $element['data']['id'];

        $forms = "" ;
        foreach ($element['data']['inputs'] as $fieldName => $fieldOptions) {

            $renderOptions = [
                'fieldDefinitions' => $fieldOptions,
            ];

            if (!empty($fieldOptions['selectOptions'])) {
                $renderOptions['selectOptions'] = $fieldOptions['selectOptions'];
            }

            if (!empty($fieldOptions['label'])) {
                $renderOptions['label'] = $fieldOptions['label'];
            }

            if (!empty($fieldOptions['lookup_field'])) {
                $renderOptions['selectOptions'] = $columns;
            }
            $fieldValue = !empty($fieldOptions['value']) ? $fieldOptions['value'] : "";

            $forms = $forms . $fhf->renderInput("item[$element_id]", $fieldName, ['value' => $fieldValue ], $renderOptions);
        }

        $templates[$element_id] = $forms;

    }
}
?>

<?php $this->Html->scriptStart(['block' => 'scriptBottom']); ?>

<?php foreach ($templates as $key => $value): ?>

Vue.component('component<?= $key ?>', {
  template: `<div>
                <div id="component<?= $key ?>" class="modal fade" tabindex="-1" role="dialog" >
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title">Titolo</h4>
                            </div>
                            <div class="modal-body">
                                <form id="form<?= $key ?>">
                                    <?= $value ?>
                                    <button name="btn_operation" value="submit" class="btn btn-primary" type="submit" @click.prevent="saveOption()">Save</button>
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`,
    methods: {
        saveOption: function() {
            let data = $("#form<?= $key ?>").serializeArray();

            Array.prototype.forEach.call (data, function (item) {
                console.log(item)
            } );
        }
    }
})

<?php endforeach ?>

new Vue({
  el: "#templates",
  template: `
    <div>
    <?php foreach ($templates as $key => $value): ?>
        <component<?= $key ?> />
    <?php endforeach ?>
    </div>
  `
})

<?= $this->Html->scriptEnd(); ?>

