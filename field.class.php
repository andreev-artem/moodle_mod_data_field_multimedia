<?php

class data_field_multimedia extends data_field_base {
    var $type = 'multimedia';
    var $imageprocessed = false;

    function data_field_multimedia($field=0, $data=0) {
        parent::data_field_base($field, $data);
    }

    function display_add_field($recordid=0) {
        global $CFG;
        require_once($CFG->libdir.'/filelib.php');

        if ($recordid){
            if ($content = get_record('data_content', 'fieldid', $this->field->id, 'recordid', $recordid)) {
                $contents[0] = $content->content;
                $contents[1] = $content->content1;
                $contents[2] = $content->content2;
                $contents[3] = $content->content3;
            } else {
                $contents[0] = '';
                $contents[1] = '';
                $contents[2] = '';
                $contents[3] = '';
            }
            $src         = empty($contents[0]) ? '' : $contents[0];
            $width       = empty($contents[1]) ? 0 : $contents[1];
            $height      = empty($contents[2]) ? 0 : $contents[2];
            $image       = empty($contents[3]) ? '' : $contents[3];
            require_once($CFG->libdir.'/filelib.php');
            $source = get_file_url($this->data->course.'/'.$CFG->moddata.'/data/'.$this->data->id.'/multimedia/'.$this->field->id.'/'.$recordid);
        } else {
            $src = '';
            $width = 0;
            $height = 0;
            $source = '';
            $image = '';
        }
        $str = '<div title="' . s($this->field->description) . '">';
        $str .= '<fieldset><legend>'.s($this->field->description).'</legend>';
        $str .= '<input type="hidden" name ="field_'.$this->field->id.'_file" value="fakevalue" />';
        $str .= '<input type="hidden" name ="field_'.$this->field->id.'_image" value="fakevalue" />';
        $str .= '<table border="0">';
        $str .= '<tr><td align="right">'.get_string('file','data'). '</td>'.
                '<td align="left"> <input type="file" name ="field_'.$this->field->id.'_realfile"
                            id="field_'.$this->field->id.'_realfile" title="'.s($this->field->description).'" /></td></tr>';
        if ($recordid and isset($content) and !empty($content->content)) {
            $icon = mimeinfo('icon', $src);
            $str .= '<tr><td>&nbsp;</td><td align="left">'.
                    '<img src="'.$CFG->pixpath.'/f/'.$icon.'" class="icon" alt="'.$icon.'" />'.
                    '<a href="'.$source.'/'.$src.'" >'.$src.'</a></td></tr>';
        }
        $str .= '<tr><td align="right">'.get_string('fieldwidth', 'data').'</td>'.
                '<td align="left"> <input type="text" name="field_' .$this->field->id.'_width"
                            id="field_'.$this->field->id.'_width" value="'.s($width).'" /></td></tr>';
        $str .= '<tr><td align="right">'.get_string('fieldheight', 'data').'</td>'.
                '<td align="left"> <input type="text" name="field_' .$this->field->id.'_height"
                            id="field_'.$this->field->id.'_height" value="'.s($height).'" /></td></tr>';
        $str .= '<tr><td align="right">'.get_string('picture', 'data').'</td>'.
                '<td align="left"> <input type="file" name="field_' .$this->field->id.'_realimage"
                            id="field_'.$this->field->id.'_realimage" value="'.s($image).'" /></td></tr>';
        if ($recordid and isset($content) and !empty($content->content3)) {
            $icon = mimeinfo('icon', $image);
            $str .= '<tr><td>&nbsp;</td><td align="left">'.
                    '<img src="'.$CFG->pixpath.'/f/'.$icon.'" class="icon" alt="'.$icon.'" />'.
                    '<a href="'.$source.'/'.$image.'" >'.$image.'</a></td></tr>';
        }
        $str .= '</table>';
        $str .= '<input type="hidden" name="MAX_FILE_SIZE" value="'.s($this->field->param3).'" />';
        $str .= '</fieldset>';
        $str .= '</div>';
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
        $parts = explode('.', $src);
        $ext = $parts[count($parts)-1];
        $width = empty($content->content1) ? 0 : $content->content1;
        $height = empty($content->content2) ? 0 : $content->content2;
        $source = get_file_url($this->data->course.'/'.$CFG->moddata.'/data/'.$this->data->id.'/multimedia/'.$this->field->id.'/'.$recordid);
        $result = '';
        if ($ext === 'flv') {
            if ($CFG->filter_mediaplugin_enable_flv) {
                $picurl = empty($content->content3) ? '' : '&image='.$source.'/'.$content->content3;
                $src .= $picurl; //hacked
                $link = '<a href="'. $source. '/'. $src. '?d='. $width. 'x'. $height. '"></a>';
                $search = '/<a.*?href="([^<]+\.flv[^"?]*)(\?d=([\d]{1,4}%?)x([\d]{1,4}%?))?"[^>]*>.*?<\/a>/is'; //hacked
                $result = preg_replace_callback($search, 'mediaplugin_filter_flv_callback', $link);
                if ($result == $link) {
                    return '';
                }
            }
        } else {
            $dim = in_array(strtolower($ext), array('mp3', 'ram', 'rpm', 'rm')) ? '' : ('?d='. $width. 'x'. $height);
            $link = '<a href="'. $source. '/'. $src. $dim. '"></a>';
            $result = mediaplugin_filter($this->data->course, $link);
            if ($result == $link) {
                // link was not processed (not a media file etc.)
                return '';
            }
        }
        return $result;
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
            case 'image':
                if (!$this->imageprocessed) {
                    $filename = $_FILES[$names[0].'_'.$names[1].'_realimage']['name'];
                    $dir = $this->data->course.'/'.$CFG->moddata.'/data/'.$this->data->id.'/multimedia/'.$this->field->id.'/'.$recordid;
                    require_once($CFG->libdir.'/uploadlib.php');
                    $course = get_record("course", "id", "{$this->data->course}");
                    //Upload image but don't delete existing files
                    $um = new upload_manager($names[0].'_'.$names[1].'_realimage',false,false,$course,false,$this->field->param3);
                    if ($um->process_file_uploads($dir)) {
                        $content->content3 = $um->get_new_filename();
                        update_record('data_content',$content);
                    }
                }
                break;

            case 'file':
                $filename = $_FILES[$names[0].'_'.$names[1].'_realfile']['name'];
                $dir = $this->data->course.'/'.$CFG->moddata.'/data/'.$this->data->id.'/multimedia/'.$this->field->id.'/'.$recordid;
                // only use the manager if file is present, to avoid "are you sure you selected a file to upload" msg
                if ($filename) {
                    require_once($CFG->libdir.'/uploadlib.php');
                    $course = get_record("course", "id", "{$this->data->course}");
                    $noimage = empty($_FILES[$names[0].'_'.$names[1].'_realimage']['name']);
                    if ($noimage) {
                        //Aviod php notification. It seems like a bug for multiply uploads (/lib/uploadlib.php at 225)
                        unset($_FILES[$names[0].'_'.$names[1].'_realimage']);
                    }
                    //Upload all choosed files with deletion all outdated
                    $um = new upload_manager('',true,false,$course,false,$this->field->param3);
                    if ($um->process_file_uploads($dir)) {
                        $content->content  = $um->files[$names[0].'_'.$names[1].'_realfile']['name'];
                        $content->content3 = $noimage ? '' : $um->files[$names[0].'_'.$names[1].'_realimage']['name'];
                        update_record('data_content',$content);
                        //We should not process image separately if it has already processed here
                        $this->imageprocessed = true;
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
            $filename = $_FILES[$names[0].'_'.$names[1].'_real'.$names[2]];
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
