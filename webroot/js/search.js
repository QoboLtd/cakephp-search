var search = search || {};

(function ($) {
    /**
     * Search Logic.
     *
     * @param {object} options configuration options
     */
    function Search(options)
    {
        this.formId = options.hasOwnProperty('formId') ? options.formId : '#SearchFilterForm';
        this.addFieldId = options.hasOwnProperty('addFieldId') ? options.addFieldId : '#addFilter';
        this.model = '';
        this.fieldProperties = {};
        this.fieldTypeOperators = {};
        this.associationLabels = {};
        this.deleteBtnHtml = '<div class="input-sm">' +
            '<a href="#" class="search-field-remover">' +
                '<span class="glyphicon glyphicon-minus"></span>' +
            '</a>' +
        '</div>';
        this.operatorSelectHtml = '<select ' +
            'name="criteria[{{field}}][{{timestamp}}][operator]" ' +
            'class="form-control input-sm">' +
            '{{options}}' +
        '</select>';
        this.operatorOptionHtml = '<option value="{{value}}" {{selected}}>{{label}}</option>';
        this.fieldTypeHtml = '<input' +
            ' type="hidden"' +
            ' name="criteria[{{field}}][{{timestamp}}][type]"' +
            ' value="{{type}}"' +
        '>';
        this.fieldLabelHtml = '<label>{{label}}</label>';
        this.fieldInputHtml = '<div class="form-group search-field-wrapper">{{fieldType}}' +
            '<div class="row">' +
                '<div class="col-xs-12 col-md-3 col-lg-2">{{fieldLabel}}</div>' +
                '<div class="col-xs-4 col-md-2 col-lg-3">{{fieldOperator}}</div>' +
                '<div class="col-xs-6 col-md-5 col-lg-4">{{fieldInput}}</div>' +
                '<div class="col-xs-2 col-lg-1">{{deleteButton}}</div>' +
            '</div>' +
        '</div>';
    }

    /**
     * Initialize method.
     *
     * @return {undefined}
     */
    Search.prototype.init = function () {
        this._onfieldSelect();
    };

    /**
     * Re-generate criteria fields on form submit.
     *
     * @param  {object} criteriaFields preset criteria fields
     * @return {undefined}
     */
    Search.prototype.generateCriteriaFields = function (criteriaFields) {
        var that = this;
        if (!$.isEmptyObject(criteriaFields)) {
            $.each(criteriaFields, function (k, v) {
                if ('object' !== typeof v) {
                    return;
                }
                $.each(v, function (i, j) {
                    that._generateField(k, that.fieldProperties[k], j.value, j.operator);
                });
            });
        }
    };

    /**
     * Search model setter.
     *
     * @param {string} model Model name
     */
    Search.prototype.setModel = function (model) {
        this.model = model;
    };

    /**
     * Field properties setter.
     *
     * @param {object} fieldProperties field properties
     */
    Search.prototype.setFieldProperties = function (fieldProperties) {
        this.fieldProperties = fieldProperties;
    };

    /**
     * Search model setter.
     *
     * @param {string} model Model name
     */
    Search.prototype.setAssociationLabels = function (associationLabels) {
        this.associationLabels = associationLabels;
    };

    /**
     * Method that generates field on field dropdown select.
     *
     * @return {undefined}
     */
    Search.prototype._onfieldSelect = function () {
        var that = this;
        $(this.addFieldId).change(function () {
            if ('' !== this.value) {
                that._generateField(this.value, that.fieldProperties[this.value]);
                this.value = '';
            }
        });
    };

    /**
     * Remove button click logic.
     *
     * @return {undefined}
     */
    Search.prototype._onRemoveBtnClick = function () {
        $(this.formId).on('click', 'a.search-field-remover', function (event) {
            event.preventDefault();
            $(this).parents('.search-field-wrapper').remove();
        });
    };

    /**
     * Method that generates form field.
     *
     * @param  {string}    field       field name
     * @param  {object}    properties  field properties
     * @param  {string}    value       field value
     * @param  {string}    setOperator field set operator
     * @return {undefined}
     */
    Search.prototype._generateField = function (field, properties, value, setOperator) {
        var timestamp = Math.round(1000000 * Math.random());

        var inputHtml = this.fieldInputHtml;
        inputHtml = inputHtml.replace('{{fieldType}}', this._generateFieldType(field, properties.type, timestamp));
        inputHtml = inputHtml.replace('{{fieldLabel}}', this._generateFieldLabel(field, properties.label));
        inputHtml = inputHtml.replace(
            '{{fieldOperator}}',
            this._generateSearchOperator(field, properties.operators, timestamp, setOperator)
        );
        inputHtml = inputHtml.replace(
            '{{fieldInput}}',
            this._generateFieldInput(field, properties.input, timestamp, value)
        );
        inputHtml = inputHtml.replace('{{deleteButton}}', this._generateDeleteButton());

        $(this.formId + ' fieldset').append(inputHtml);

        this._onRemoveBtnClick();
    };

    /**
     * Generates and returns field label html.
     *
     * @param  {string} field field name
     * @param  {string} label field label
     * @return {string}
     */
    Search.prototype._generateFieldLabel = function (field, label) {
        var input = this.fieldLabelHtml;

        var tableName = field.substr(0, field.indexOf('.'));

        var suffix = '';
        if (this.model !== tableName) {
            suffix = this.associationLabels.hasOwnProperty(tableName) ?
                this.associationLabels[tableName] :
                tableName;
            suffix = ' (' + suffix + ')';
        }

        return input.replace('{{label}}', label + suffix);
    };

    /**
     * Generates and returns field type hidden input html.
     *
     * @param  {string} field     field name
     * @param  {string} type      field type
     * @param  {number} timestamp timestamp
     * @return {string}
     */
    Search.prototype._generateFieldType = function (field, type, timestamp) {
        var input = this.fieldTypeHtml;

        return input.replace('{{field}}', field).replace('{{timestamp}}', timestamp).replace('{{type}}', type);
    };

    /**
     * Generates and returns field operator html.
     *
     * @param  {string} field       field name
     * @param  {string} type        field type
     * @param  {number} timestamp   timestamp
     * @param  {string} setOperator field set operator
     * @return {string}
     */
    Search.prototype._generateSearchOperator = function (field, operators, timestamp, setOperator) {
        var that = this;

        var options = '';
        $.each(operators, function (k, v) {
            var option = that.operatorOptionHtml;
            option = option.replace('{{value}}', k);
            option = option.replace('{{label}}', v.label);
            if (k === setOperator) {
                option = option.replace('{{selected}}', 'selected');
            } else {
                option = option.replace('{{selected}}', '');
            }
            options += option;
        });

        var select = this.operatorSelectHtml;

        return select.replace('{{field}}', field).replace('{{timestamp}}', timestamp).replace('{{options}}', options);
    };

    /**
     * Generates and returns field input html.
     *
     * @param  {string} field      field name
     * @param  {object} properties field properties
     * @param  {number} timestamp  timestamp
     * @param  {string} value      field value
     * @return {string}
     */
    Search.prototype._generateFieldInput = function (field, input, timestamp, value) {
        var name = 'criteria[' + field + '][' + timestamp + '][value]';
        if ('undefined' === typeof value) {
            value = '';
        }

        var result = input.content
            .replace(/{{name}}/g, name)
            .replace(/{{value}}/g, value)
            .replace(/{{id}}/g, timestamp)
            .replace(/(["|\s])(input-group)(["|\s])/g, '$1$2 input-group-sm$3')
            .replace(/(["|\s])(form-control)(["|\s])/g, '$1$2 input-sm$3');

        if (value) {
            result = this._handleSpecialInputs(result, value);
        }

        return result;
    };

    /**
     * Handle special inputs such as checkbox and select.
     * Select will set the correct option as 'selected'
     * and checkbox will set the checked flag if value
     * is true.
     *
     * @param  {string} element Input element
     * @param  {string} value   Input value
     * @return {string}
     */
    Search.prototype._handleSpecialInputs = function (element, value) {
        var html = $(element);

        // handle select element
        var has_select = $(html).find('select');
        if (html.is('select') || 0 < has_select.length) {
            $(html).find('option').each(function () {
                if (this.value !== value) {
                    return true;
                }
                $(this).attr('selected', 'selected');

                return false;
            });

            return html.get(0).outerHTML;
        }

        // handle checkbox element
        var has_checkbox = $(html).find(':checkbox');
        if (html.is(':checkbox') || 0 < has_checkbox.length) {
            $(html).find(':checkbox').each(function () {
                // convert string to int with + and then to boolean with !!
                // @link http://stackoverflow.com/a/16313488/2562232
                var checked = !! + value;
                $(this).attr('checked', checked);

                return false;
            });

            return html.get(0).outerHTML;
        }

        return element;
    };

    /**
     * Generates and returns field delete button html.
     *
     * @return {string}
     */
    Search.prototype._generateDeleteButton = function () {
        var button = this.deleteBtnHtml;

        return button;
    };

    search = new Search({
        addFieldId: '#addFilter',
        formId: '#SearchFilterForm'
    });

    search.init();

})(jQuery);
