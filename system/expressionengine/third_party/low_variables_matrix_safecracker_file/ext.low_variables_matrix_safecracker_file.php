<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * Low Variables Matrix Safecracker File Extension
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Extension
 * @author		Rob Sanchez
 * @link		
 */

class Low_variables_matrix_safecracker_file_ext {
	
	public $settings 		= array();
	public $description		= 'Adds Safecracker File compatibility in Matrix in Low Variables';
	public $docs_url		= '';
	public $name			= 'Low Variables Matrix Safecracker File';
	public $settings_exist	= 'n';
	public $version			= '1.0';
	
	private $EE;
	
	/**
	 * Constructor
	 *
	 * @param 	mixed	Settings array or empty string if none exist.
	 */
	public function __construct($settings = '')
	{
		$this->EE =& get_instance();
		$this->settings = $settings;
	}// ----------------------------------------------------------------------
	
	/**
	 * Activate Extension
	 *
	 * This function enters the extension into the exp_extensions table
	 *
	 * @see http://codeigniter.com/user_guide/database/index.html for
	 * more information on the db class.
	 *
	 * @return void
	 */
	public function activate_extension()
	{
		// Setup custom settings in this array.
		$this->settings = array();
		
		$data = array(
			'class'		=> __CLASS__,
			'method'	=> 'matrix_save_row',
			'hook'		=> 'matrix_save_row',
			'settings'	=> serialize($this->settings),
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);

		$this->EE->db->insert('extensions', $data);			
		
	}	

	// ----------------------------------------------------------------------
	
	/**
	 * matrix_save_row
	 *
	 * @param 
	 * @return 
	 */
	public function matrix_save_row($matrix, $row)
	{
        static $new_row_cache = array();

        //get any previous calls of this extension
        if ($this->EE->extensions->last_call !== FALSE)
        {
            $row = $this->EE->extensions->last_call;
        }

        //quit if we're not in low variables
        if (empty($matrix->var_id))
        {
            return $row;
        }

        //keep a cache of new rows and their "new" row id
        if ( ! isset($new_row_cache[$matrix->var_id]))
        {
            $new_row_cache[$matrix->var_id] = 0;
        }

        //interpolate the row name
        $row_name = isset($row['row_id']) ? 'row_id_'.$row['row_id'] : 'row_new_'.$new_row_cache[$matrix->var_id]++;

        //if this data is not cached we should not continue
        if ( ! isset($matrix->cache['field_cols']['var'.$matrix->var_id]))
        {
            return $row;
        }

        //find any cols that are safecracker file celltypes
        $safecracker_file_cols = array();

        foreach ($matrix->cache['field_cols']['var'.$matrix->var_id] as $col)
        {
            if ($col['col_type'] === 'safecracker_file')
            {
                $safecracker_file_cols[] = $col;
            }
        }

        //there are no safecracker files here, bye bye
        if (empty($safecracker_file_cols))
        {
            return $row;
        }

        //re-save these files
        foreach ($safecracker_file_cols as $col)
        {
            //create a new instance of the fieldtype
            $celltype = new Safecracker_file_ft;

            //grab the cached global settings for sc file
            if (isset($matrix->cache['celltype_global_settings']['safecracker_file']) && is_array($matrix->cache['celltype_global_settings']['safecracker_file']))
            {
                $celltype->settings = $matrix->cache['celltype_global_settings']['safecracker_file'];
            }

            //if this ain't in post, there's nothing to do
            if ( ! isset($_POST['var'][$matrix->var_id]))
            {
                continue;
            }

            $var_name = 'var['.$matrix->var_id.']';

            //trick SC file into believing that a normal "cell" is being used
            $_POST[$var_name] = $_POST['var'][$matrix->var_id];

            //trick SC file into believe that a normal "cell" FILE has been uploaded
            if (isset($_FILES['var']['name'][$matrix->var_id]))
            {
                foreach (array('name', 'type', 'tmp_name', 'error', 'size') as $key)
                {
                    $_FILES[$var_name][$key] = $_FILES['var'][$key][$matrix->var_id];
                }
            }

            //set the cell's settings
            $celltype->settings = array_merge($celltype->settings, $col['celltype_settings']);

            $celltype->settings['row_name'] = $row_name;

            //run the save_cell method again (this time it will see the file upload and work accordingly)
            $row['col_id_'.$col['col_id']] = $celltype->save_cell(NULL);

            //remove these so they don't interfere with subsequent rows
            unset($_POST[$var_name], $_FILES[$var_name]);
        }

        return $row;
	}

	// ----------------------------------------------------------------------

	/**
	 * Disable Extension
	 *
	 * This method removes information from the exp_extensions table
	 *
	 * @return void
	 */
	function disable_extension()
	{
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('extensions');
	}

	// ----------------------------------------------------------------------

	/**
	 * Update Extension
	 *
	 * This function performs any necessary db updates when the extension
	 * page is visited
	 *
	 * @return 	mixed	void on update / false if none
	 */
	function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
	}	
	
	// ----------------------------------------------------------------------
}

/* End of file ext.low_variables_matrix_safecracker_file.php */
/* Location: /system/expressionengine/third_party/low_variables_matrix_safecracker_file/ext.low_variables_matrix_safecracker_file.php */