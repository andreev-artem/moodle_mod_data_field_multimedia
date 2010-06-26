<?php

class data_field_multimedia extends data_field_base {
    var $type = 'multimedia';

    function data_field_multimedia($field=0, $data=0) {
        parent::data_field_base($field, $data);
    }

    function display_add_field($recordid=0) {
        global $CFG;
        if ($recordid){
            if ($content = get_record('data_content', 'fieldid', $this->field->id, 'recordid', $recordid)) {
                $contents[0] = $content->content;
                $contents[1] = $content->content1;
                $contents[2] = $content->content2;
            } else {
                $contents[0] = '';
                $contents[1] = '';
                $contents[2] = '';
            }
            $src         = empty($contents[0]) ? '' : $contents[0];
            $width       = empty($contents[1]) ? 0 : $contents[1];
            $height      = empty($contents[2]) ? 0 : $contents[2];
            require_once($CFG->libdir.'/filelib.php');
            $source = get_file_url($this->data->course.'/'.$CFG->moddata.'/data/'.$this->data->id.'/multimedia/'.$this->field->id.'/'.$recordid);
        } else {
            $src = '';
            $width = 0;
            $height = 0;
            $source = '';
        }
        $str = '<div title="' . s($this->field->description) . '">';
        $str .= '<fieldset><legend><span class="accesshide">'.$this->field->name.'</span></legend>';
        $str .= '<input type="hidden" name ="field_'.$this->field->id.'_file" value="fakevalue" />';
        $str .= get_string('file','data'). ' <input type="file" name ="field_'.$this->field->id.'" id="field_'.
                            $this->field->id.'" title="'.s($this->field->description).'" /><br />';
        $str .= get_string('fieldwidth', 'data').' <input type="text" name="field_' .$this->field->id.'_width"
                            id="field_'.$this->field->id.'_width" value="'.s($width).'" /><br />';
        $str .= get_string('fieldheight', 'data').' <input type="text" name="field_' .$this->field->id.'_height"
                            id="field_'.$this->field->id.'_height" value="'.s($height).'" /><br />';
        $str .= '<input type="hidden" name="MAX_FILE_SIZE" value="'.s($this->field->param3).'" />';
        $str .= '</fieldset>';
        $str .= '</div>';
        if ($recordid and isset($content) and !empty($content->content)) {
            // Print icon
            require_once($CFG->libdir.'/filelib.php');
            $icon = mimeinfo('icon', $src);
            $str .= '<img src="'.$CFG->pixpath.'/f/'.$icon.'" class="icon" alt="'.$icon.'" />'.
                    '<a href="'.$source.'/'.$src.'" >'.$src.'</a>';
        }
        return $str;
    }

    function display_search_field($value = '') {
        return '<input type="text" size="16" name="f_'.$this->field->id.'" value="'.$value.'" />';
    }

    function generate_sql($tablealias, $value) {
        return " ({$tablealias}.fieldid = {$this->field->id} AND {$tablealias}.content LIKE '%{$value}%') ";
    }

    function parse_search_field() {
        return optional_param('f_'.$this->field->id, '', PARAM_NOTAGS);
    }

    function display_browse_field($recordid, $template) {
        global $CFG;
        if (!$content = get_record('data_content', 'fieldid', $this->field->id, 'recordid', $recordid)) {
            return false;
        }
        if (empty($content->content)) {
            return '';
        }
        require_once($CFG->libdir.'/filelib.php');
        require_once($CFG->dirroot.'/filter/mediaplugin/filter.php');
        $src  = $content->content;
        $width = empty($content->content1) ? 0 : $content->content1;
        $height = empty($content->content2) ? 0 : $content->content2;
        $source = get_file_url($this->data->course.'/'.$CFG->moddata.'/data/'.$this->data->id.'/multimedia/'.$this->field->id.'/'.$recordid);
        $link = '<a href="'. $source. '/'. $src. '?d='. $width. 'x'. $height. '"></a>';
        $str = mediaplugin_filter($this->data->course, $link);
        if ($str == $link) {
            // link was not processed (not a media file etc.)
            return "";
        }
        return $str;
    }


    function update_content($recordid, $value, $name) {
        global $CFG;
        if (!($oldcontent = get_record('data_content','fieldid', $this->field->id, 'recordid', $recordid))) {
        // Quickly make one now!
            $oldcontent = new object;
            $oldcontent->fieldid = $this->field->id;
            $oldcontent->recordid = $recordid;
            if (!($oldcontent->id = insert_record('data_content', $oldcontent))) {
                error('Could not make an empty record!');
            }
        }
        $content = new object;
        $content->id = $oldcontent->id;
        $names = explode('_',$name);
        switch ($names[2]) {
            case 'file':
                $filename = $_FILES[$names[0].'_'.$names[1]];
                $filename = $filename['name'];
                $dir = $this->data->course.'/'.$CFG->moddata.'/data/'.$this->data->id.'/multimedia/'.$this->field->id.'/'.$recordid;
                // only use the manager if file is present, to avoid "are you sure you selected a file to upload" msg
                if ($filename) {
                    require_once($CFG->libdir.'/uploadlib.php');
                    $course = get_record("course", "id", "{$this->data->course}");
                    $um = new upload_manager($names[0].'_'.$names[1],true,false,$course,false,$this->field->param3);
                    if ($um->process_file_uploads($dir)) {
                        $newfile_name = $um->get_new_filename();
                        $content->content = $newfile_name;
                        update_record('data_content',$content);
                    }
                }
                break;

            case 'width':
                $content->content1 = clean_param($value, PARAM_NOTAGS);
                update_record('data_content', $content);
                break;
                
            case 'height':
                $content->content2 = clean_param($value, PARAM_NOTAGS);
                update_record('data_content', $content);
                break;

            default:
                break;
        }
    }

    function notemptyfield($value, $name) {
        $names = explode('_',$name);
        if ($names[2] == 'file') {
            $filename = $_FILES[$names[0].'_'.$names[1]];
            return !empty($filename['name']);
            // if there's a file in $_FILES, not empty
        }
        return false;
    }

    function text_export_supported() {
        return false;
    }

}

function multimedia_field_moddata_trusted() {
    return true;
}

?>
