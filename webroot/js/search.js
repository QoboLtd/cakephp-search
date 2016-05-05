var search = search || {};

(function($) {
    /**
     * Search Logic.
     *
     * @param {object} options configuration options
     */
    function Search(options) {
        this.formId = options.hasOwnProperty('formId') ? options.formId : '#SearchFilterForm';
        this.addFieldId = options.hasOwnProperty('addFieldId') ? options.addFieldId : '#addFilter';
        this.fieldProperties = {};
        this.fieldTypeOperators = {};
    }

    /**
     * Initialize method.
     *
     * @return {undefined}
     */
    Search.prototype.init = function() {
        this._onfieldSelect();
    };

    /**
     * Re-generate criteria fields on form submit.
     *
     * @param  {object} criteriaFields preset criteria fields
     * @return {undefined}
     */
    Search.prototype.generateCriteriaFields = function(criteriaFields) {
        if (!$.isEmptyObject(criteriaFields)) {
            $.each(criteriaFields, function(k, v) {
                if ('object' === typeof v) {
                    data = v[Object.keys(v)[0]];
                    that._generateField(k, that.fieldProperties[k], data.value, data.operator);
                }
            });
        }
    };

    /**
     * Field properties setter.
     *
     * @param {object} fieldProperties field properties
     */
    Search.prototype.setFieldProperties = function(fieldProperties) {
        this.fieldProperties = fieldProperties;
    };

    /**
     * Field type operators setter.
     *
     * @param {object} fieldTypeOperators field type operators
     */
    Search.prototype.setFieldTypeOperators = function(fieldTypeOperators) {
        this.fieldTypeOperators = fieldTypeOperators;
    };

    /**
     * Method that generates field on field dropdown select.
     *
     * @return {undefined}
     */
    Search.prototype._onfieldSelect = function() {
        that = this;
        $(this.addFieldId).change(function() {
            if ('' !== this.value) {
                that._generateField(this.value, that.fieldProperties[this.value]);
                this.value = '';
            }
        });
    };

    /**
     * Remove button click logic.
     * @param  {string} id button id
     * @return {undefined}
     */
    Search.prototype._onRemoveBtnClick = function(id) {
        $('#' + id).on('click', 'a', function(event) {
            event.preventDefault();
            $('#' + $(this).data('element-id')).remove();
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
    Search.prototype._generateField = function(field, properties, value, setOperator) {
        var timestamp = new Date().getUTCMilliseconds();
        var del_id = field + '_' + timestamp;
        var inputHtml = '';
        inputHtml += '<div class="form-group" id="' + del_id + '">';
            inputHtml += this._generateFieldLabel(properties.label);
            inputHtml += this._generateFieldType(field, properties.type, timestamp);
            inputHtml += '<div class="row">';
                inputHtml += '<div class="col-xs-5 col-md-4 col-lg-3">';
                    inputHtml += this._generateFieldOperator(field, properties.type, timestamp, setOperator);
                inputHtml += '</div>';
                inputHtml += '<div class="col-xs-5 col-md-5 col-lg-4">';
                    inputHtml += this._generateFieldInput(field, properties, timestamp, value);
                inputHtml += '</div>';
                inputHtml += '<div class="col-xs-2 col-md-1">';
                    inputHtml += this._generateDeleteButton(del_id);
                inputHtml += '</div>';
            inputHtml += '</div>';
        inputHtml += '</div>';
        $(this.formId + ' fieldset').append(inputHtml);

        this._onRemoveBtnClick(del_id);
    };

    /**
     * Generates and returns field label html.
     *
     * @param  {object} label field label
     * @return {string}
     */
    Search.prototype._generateFieldLabel = function(label) {
        var result = '';
        result += '<label>' + label + '</label>';

        return result;
    };

    /**
     * Generates and returns field type hidden input html.
     *
     * @param  {string} field     field name
     * @param  {string} type      field type
     * @param  {string} timestamp timestamp
     * @return {string}
     */
    Search.prototype._generateFieldType = function(field, type, timestamp) {
        var result = '';
        result += '<input type="hidden" name="' + field + '[' + timestamp + '][type]" value="' + type + '">';

        return result;
    };

    /**
     * Generates and returns field operator html.
     *
     * @param  {string} field       field name
     * @param  {string} type        field type
     * @param  {string} timestamp   timestamp
     * @param  {string} setOperator field set operator
     * @return {string}
     */
    Search.prototype._generateFieldOperator = function(field, type, timestamp, setOperator) {
        var result = '';
        if (this.fieldTypeOperators.hasOwnProperty(type)) {
            result += '<select name="' + field + '[' + timestamp + '][operator]" class="form-control input-sm">';
            $.each(this.fieldTypeOperators[type], function(k, v) {
                result += '<option value="' + k + '"';
                if (setOperator === k) {
                    result += ' selected';
                }
                result += '>';
                result += v + '</option>';
            });
            result += '</select>';
        }

        return result;
    };

    /**
     * Generates and returns field input html.
     *
     * @param  {string} field      field name
     * @param  {object} properties field properties
     * @param  {string} timestamp  timestamp
     * @param  {string} value      field value
     * @return {string}
     */
    Search.prototype._generateFieldInput = function(field, properties, timestamp, value) {
        var result = '';
        if ('undefined' === typeof value) {
            value = '';
        }
        switch (properties.type) {
            case 'list':
                result += '<select name="' + field + '[' + timestamp + '][value]" class="form-control input-sm">';
                $.each(properties.fieldOptions, function(k, v) {
                    result += '<option value="' + k + '">' + v + '</option>';
                });
                result += '</select>';
                break;
            case 'boolean':
                result += '<input type="hidden" name="' + field + '[' + timestamp + '][value]" value="0">';
                result += '<input type="checkbox" name="' + field + '[' + timestamp + '][value]" = value="1">';
                break;
            default:
                result += '<input type="' + properties.type + '" name="' + field + '[' + timestamp + '][value]"';
                result += ' class="form-control input-sm" value="' + value + '">';
        }

        return result;
    };

    /**
     * Generates and returns field delete button html.
     *
     * @param  {string} id field id
     * @return {string}
     */
    Search.prototype._generateDeleteButton = function(id) {
        var result = '';
        result += '<div class="input-sm">';
            result += '<a href="#" data-element-id="' + id + '">';
                result += '<span class="glyphicon glyphicon-minus"></span>';
            result += '</a>';
        result += '</div>';

        return result;
    };

    search = new Search({
        addFieldId: '#addFilter',
        formId: '#SearchFilterForm'
    });

    search.init();

})(jQuery);
