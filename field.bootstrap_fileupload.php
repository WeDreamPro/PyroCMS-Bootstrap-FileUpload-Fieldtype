<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PyroStreams Bootstrap File Upload Field Type
 *
 * @package		PyroCMS\Core\Modules\Streams Core\Field Types
 * @author		Rigo B Castro
 * @author		https://github.com/jasny/bootstrap#authors
 * @copyright           Copyright (c) 2011 - 2013
 * @license		https://github.com/jasny/bootstrap/blob/master/LICENSE
 * @link        https://github.com/rigobcastro/PyroCMS-Bootstrap-FileUpload-Fieldtype
 * @link		http://rigobcastro.com
 * @link		http://jasny.github.io/bootstrap/index.html
 */
class Field_bootstrap_fileupload {

    public $field_type_slug = 'bootstrap_fileupload';
    public $db_col_type = 'char';
    public $col_constraint = 15;
    public $custom_parameters = array('folder', 'allowed_types');
    public $version = '1.0.0';
    public $author = array('name' => 'Rigo B Castro', 'url' => 'http://rigobcastro.com');
    public $input_is_file = true;

    // --------------------------------------------------------------------------

    /**
     * Output form input
     *
     * @param	array
     * @param	array
     * @return	string
     */
    public function form_output($params)
    {

        $this->CI->load->config('files/files');

        // Get the file
        if ($params['value'])
        {
            $current_file = $this->CI->db
                ->where('id', $params['value'])
                ->limit(1)
                ->get('files')
                ->row();
        }
        else
        {
            $current_file = null;
        }

        $input_file_name = $params['form_slug'] . '_file';
        $out = null;
        $accept_input_html5 = null;

        if (!empty($params['custom']['allowed_types']))
        {
            $allowed_types = explode('|', $params['custom']['allowed_types']);
            
            foreach ($allowed_types as $type)
            {
                $accept_input_html5 .= ".{$type}";
                if (next($allowed_types) == true)
                    $accept_input_html5 .= ",";
            }
        }

        $out .= '<div class="fileupload fileupload-' . ($current_file ? 'exists' : 'new') . '" data-provides="fileupload" data-name="' . $input_file_name . '">
            <div class="input-append">
                <div class="uneditable-input span3">
                    <i class="icon-file fileupload-exists"></i> 
                    <span class="fileupload-preview">' . ($current_file ? anchor(base_url('files/download/' . $current_file->id), $current_file->name) : null) . '</span>
                </div>
                <span class="btn btn-file">
                    <span class="fileupload-new">' . lang('streams:bootstrap_fileupload.button:select') . '</span><span class="fileupload-exists">' . lang('streams:bootstrap_fileupload.button:change') . '</span>
                    <input type="file" name="' . $input_file_name . '" accept="' . $accept_input_html5 . '">
                </span>
                <a href="#" class="btn fileupload-exists" data-dismiss="fileupload">' . lang('streams:bootstrap_fileupload.button:remove') . '</a>
            </div>
        </div>';

        // Output the actual used value
        if ($params['value'])
        {
            $out .= form_hidden($params['form_slug'], $params['value']);
        }
        else
        {
            $out .= form_hidden($params['form_slug'], 'dummy');
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    public function event()
    {
        $this->CI->type->add_css('bootstrap_fileupload', 'bootstrap-fileupload.css');
        $this->CI->type->add_js('bootstrap_fileupload', 'bootstrap-fileupload.js');
    }

    // ----------------------------------------------------------------------

    public function pre_save($input, $field)
    {
        // If we do not have a file that is being submitted. If we do not,
        // it could be the case that we already have one, in which case just
        // return the numeric file record value.
        if (!isset($_FILES[$field->field_slug . '_file']['name']) or !$_FILES[$field->field_slug . '_file']['name'])
        {
            if ($this->CI->input->post($field->field_slug))
            {
                return $this->CI->input->post($field->field_slug);
            }
            else
            {
                return null;
            }
        }

        $this->CI->load->library('files/files');

        // If you don't set allowed types, we'll set it to allow all.
        $allowed_types = (isset($field->field_data['allowed_types'])) ? $field->field_data['allowed_types'] : '*';

        $return = Files::upload($field->field_data['folder'], null, $field->field_slug . '_file', null, null, null, $allowed_types);

        if (!$return['status'])
        {
            $this->CI->session->set_flashdata('notice', $return['message']);

            return null;
        }
        else
        {
            // Return the ID of the file DB entry
            return $return['data']['id'];
        }
    }

    // ----------------------------------------------------------------------

    /**
     * Process before outputting
     *
     * @access	public
     * @param	array
     * @return	mixed - null or string
     */
    public function pre_output($input, $params)
    {
        if (!$input)
            return null;

        $this->CI->load->config('files/files');

        $file = $this->CI->db
                ->limit(1)
                ->select('name')
                ->where('id', $input)
                ->get('files')->row();

        if ($file)
        {
            return '<a href="' . base_url('files/download/' . $input) . '">' . $file->name . '</a>';
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Choose a folder to upload to.
     *
     * @access	public
     * @param	[string - value]
     * @return	string
     */
    public function param_folder($value = null)
    {
        // Get the folders
        $this->CI->load->model('files/file_folders_m');

        $tree = $this->CI->file_folders_m->get_folders();

        $tree = (array) $tree;

        if (!$tree)
        {
            return '<em>' . lang('streams:file.folder_notice') . '</em>';
        }

        $choices = array();

        foreach ($tree as $tree_item)
        {
            // We are doing this to be backwards compat
            // with PyroStreams 1.1 and below where
            // This is an array, not an object
            $tree_item = (object) $tree_item;

            $choices[$tree_item->id] = $tree_item->name;
        }

        return form_dropdown('folder', $choices, $value);
    }

    // --------------------------------------------------------------------------

    /**
     * Param Allowed Types
     *
     * @access	public
     * @param	[string - value]
     * @return	string
     */
    public function param_allowed_types($value = null)
    {
        $instructions = '<p class="note">' . lang('streams:file.allowed_types_instructions') . '</p>';

        return '<div style="float: left;">' . form_input('allowed_types', $value) . $instructions . '</div>';
    }

}
