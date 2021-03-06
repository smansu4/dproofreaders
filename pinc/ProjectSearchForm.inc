<?php
include_once($relPath.'iso_lang_list.inc');

class ProjectSearchWidget
{
    public $can_be_multiple = false;
    public $initial_value = '';

    function __construct( $properties )
    {
        foreach ( $properties as $property => $value )
        {
            $this->$property = $value;
        }
    }

    function get_html_control($search_data)
    {
        # make all widgets 100% width
        $size_attr = "style='width: 100%;'";

        // If you don't specify a size for a <select> control,
        // browsers vary widely in what they use for a default.
        // (e.g., Firefox 1.0 uses 20, IE 5.5 and Opera 8 use 4, Opera 9 uses 1.)
        // To avoid this, set a reasonable size.
        if ($this->type == 'select')
        {
            $co = count($this->options);
            $this->size = ( $co <= 6 ? $co : 4 );
            $size_attr .= " size='{$this->size}'";
        }

        if ( $this->type == 'text' )
        {
            if ( isset($search_data[$this->id]) )
            {
                $value_attr = "value='" . attr_safe($search_data[$this->id]) . "'";
            }
            else // initial_value is set for base class widget
            {
                $value_attr = "value='" . attr_safe($this->initial_value) . "'";
            }
            $placeholder = $this->can_be_multiple ? "placeholder = 'list ok'" : "";
            return "<input type='text' $placeholder name='{$this->id}' $size_attr $value_attr>";
        }
        else if ( $this->type == 'select' )
        {
            if ( $this->can_be_multiple )
            {
                $r = "<select name='{$this->id}[]' $size_attr multiple>\n";
            }
            else
            {
                $r = "<select name='$this->id' $size_attr>\n";
            }
            foreach ( $this->options as $option_value => $option_label )
            {
                if(isset($search_data[$this->id]))
                {
                    if(($this->can_be_multiple) && is_array($search_data[$this->id]))
                        $selected = in_array($option_value, $search_data[$this->id]);
                    else
                        $selected = ($option_value == $search_data[$this->id]);
                }
                else
                    $selected = ($option_value == $this->initial_value);
                $selected_attr = $selected ? 'selected' : '';
                $r .= "<option value='" . attr_safe($option_value) . "' $selected_attr>" . html_safe($option_label) . "</option>\n";
            }
            $r .= "</select>\n";
            return $r;
        }
    }

    function echo_search_item($search_data)
    {
        // could fix width of heading cells so they line up when 2nd table is below
        echo "
            <tr>
                <th class='right-align top-align'>$this->label</th>
                <td class='search-input top-align left-align'>".$this->get_html_control($search_data)."</td>
            </tr>";
    }

    function get_sql_contribution($search_data)
    {
        if (!isset($search_data[$this->id]))
            return NULL;
        $value = $search_data[$this->id];
        if ( $value == '' )
            return NULL;
        list($column_name,$comparator) = $this->q_contrib;
        if ( @$this->can_be_multiple )
        {
            if ( $this->type == 'text' )
            {
                $values = preg_split( "($this->separator)",  trim($value) );
            }
            elseif ( $this->type == 'select' )
            {
                $values = $value;

                // If $value isn't an array, someone is mucking with
                // the URL -- return instead of erroring out below.
                if( !is_array($values) )
                    return NULL;
                // If the user picks the 'any' option as well as some others,
                // remove the any option.
                if (($key = array_search('', $values)) !== false)
                    unset($values[$key]);
                if(empty($values))
                    return NULL;
            }

            $values = array_map("escape_value", $values);

            if ( $comparator == '=' )
            {
                $values_list = surround_and_join( $values, "'", "'", "," );
                $contribution = "$column_name IN ($values_list)";
            }
            elseif ( $comparator == 'LIKE' )
            {
                $likes_str = surround_and_join( $values, "$column_name LIKE '%", "%'", ' OR ' );
                $contribution = "($likes_str)";
            }
        }
        else
        {
            $value = mysqli_real_escape_string(DPDatabase::get_connection(), $value);
            if ( $comparator == '=' )
            {
                $contribution = "$column_name = '$value'";
            }
            elseif ( $comparator == 'LIKE' )
            {
                $contribution = "$column_name LIKE '%$value%'";
            }
        }
        return $contribution;
    }
}

class HoldWidget extends ProjectSearchWidget
{
    public function get_html_control($search_data)
    {
        $check = isset($search_data[$this->id]) ? " checked" : "";
        return "<input type='checkbox' name='$this->id'$check>";
    }

    public function get_sql_contribution($search_data)
    {
        if(isset($search_data[$this->id])) // can only be 'on'
            return 'project_holds.projectid IS NOT NULL';
        else
            return '';
    }
}

class ProjectSearchForm
{
    public function __construct()
    {
        $this->define_form_widgets();
    }

    private function _get_options_special_day()
    {
        $special_day_options = array();
        $special_day_options[''] = _('Any day');
        $special_day_res = mysqli_query(DPDatabase::get_connection(), "
            SELECT
                spec_code,
                display_name,
                DATE_FORMAT(concat('2000-',open_month,'-',open_day),'%e %b')
            FROM special_days
            WHERE enable = 1
            ORDER BY open_month, open_day
        ");
        while ( list($s_code, $s_display_name, $s_start) = mysqli_fetch_row($special_day_res) )
        {
            $special_day_options[$s_code] = "$s_display_name ($s_start)";
        }
        return $special_day_options;
    }

    private function _get_options_lang()
    {
        global $lang_list;

        $lang_options[''] = _('Any');
        foreach($lang_list as $k=>$v)
        {
            $lang_options[$v['lang_name']] = $v['lang_name'];
        }
        return $lang_options;
    }

    private function _get_options_genre()
    {
        global $relPath;
        include($relPath.'genres.inc');

        return array_merge( array( '' => _('Any') ), $GENRES );
    }

    private function _get_options_difficulty()
    {
        return array(
            ''         => _('Any'),
            'beginner' => _('Beginner'),
            'easy'     => _('Easy'),
            'average'  => _('Average'),
            'hard'     => _('Hard'),
        );
    }

    private function _get_options_state()
    {
        global $PROJECT_STATES_IN_ORDER;

        $state_options[''] = _('Any state');
        foreach ($PROJECT_STATES_IN_ORDER as $proj_state)
        {
            $state_options[$proj_state] = project_states_text($proj_state);
        }
        return $state_options;
    }

    public function define_form_widgets()
    {
        $this->widgets = array(
            new ProjectSearchWidget( array(
                'id'         => 'title',
                'label'      => _('Title'),
                'type'       => 'text',
                'q_contrib'  => array('nameofwork', 'LIKE'),
            )),
            new ProjectSearchWidget( array(
                'id'         => 'author',
                'label'      => _('Author'),
                'type'       => 'text',
                'q_contrib'  => array('authorsname', 'LIKE'),
            )),
            new ProjectSearchWidget( array(
                'id'         => 'projectid',
                'label'      => _('Project ID'),
                'type'       => 'text',
                'can_be_multiple' => TRUE,
                'separator'  => '[\s,;]+',
                'q_contrib'  => array('projectid', 'LIKE'),
            )),
            new ProjectSearchWidget( array(
                'id'         => 'project_manager',
                'label'      => _('Project Manager'),
                'type'       => 'text',
                'q_contrib'  => array('username', 'LIKE'),
            )),
            new ProjectSearchWidget( array(
                'id'         => 'checkedoutby',
                'label'      => _('Checked Out By'),
                'type'       => 'text',
                'q_contrib'  => array('checkedoutby', 'LIKE'),
            )),
            new ProjectSearchWidget( array(
                'id'         => 'pp_er',
                'label'      => _('Post-processor'),
                'type'       => 'text',
                'q_contrib'  => array('postproofer', 'LIKE'),
            )),
            new ProjectSearchWidget( array(
                'id'         => 'ppv_er',
                'label'      => _('Post-processing Verifier'),
                'type'       => 'text',
                'q_contrib'  => array('ppverifier', 'LIKE'),
            )),
            new ProjectSearchWidget( array(
                'id'           => 'postednum',
                'label'        => _('PG etext number'),
                'type'         => 'text',
                'can_be_multiple' => TRUE,
                'separator'  => '[\s,;]+',
                'q_contrib'    => array('postednum', '='),
            )),
            new ProjectSearchWidget( array(
                'id'         => 'language',
                'label'      => _('Language'),
                'type'       => 'select',
                'options'    => $this->_get_options_lang(),
                'can_be_multiple' => TRUE,
                'initial_value'   => '',
                'q_contrib'  => array('language', 'LIKE'),
            )),
            new ProjectSearchWidget( array(
                'id'         => 'genre',
                'label'      => _('Genre'),
                'type'       => 'select',
                'options'    => $this->_get_options_genre(),
                'can_be_multiple' => TRUE,
                'q_contrib'  => array('genre', '='),
            )),
            new ProjectSearchWidget( array(
                'id'         => 'difficulty',
                'label'      => _('Difficulty'),
                'type'       => 'select',
                'options'    => $this->_get_options_difficulty(),
                'can_be_multiple' => TRUE,
                'q_contrib'  => array('difficulty', '='),
            )),
            new ProjectSearchWidget( array(
                'id'         => 'special_day',
                'label'      => _('Special day'),
                'type'       => 'select',
                'options'    => $this->_get_options_special_day(),
                'can_be_multiple' => TRUE,
                'initial_value'   => '',
                'q_contrib'  => array('special_code', '='),
            )),
            new ProjectSearchWidget( array(
                'id'           => 'state',
                'label'        => pgettext('project state', 'State'),
                'type'         => 'select',
                'options'      => $this->_get_options_state(),
                'can_be_multiple' => TRUE,
                'q_contrib'    => array('state', '='),
            )),
            new HoldWidget( array(
                'id'        => 'hold',
                'label'     => _('Projects on Hold'),
            )),
        );
    }

    public function render()
    {
        if(isset($_SESSION['search_data']))
            $search_data = $_SESSION['search_data'];
        else
            $search_data = array(); // empty array to avoid problems
        echo "<p>" . _("Search for projects matching the following criteria:")."</p>\n";
        // query data can be too big for GET so use POST
        echo "<div class='search-columns'>
            <form style='display: inline;' method='POST' action='{$_SERVER['PHP_SELF']}?show=p_search'>";
        // split the widgets into two tables which can be side-by-side if there
        // is enough room
        $widget_index = 0;
        $widget_count = count($this->widgets);
        $table_header = "<table class='search-column'>";
        echo $table_header;
        while($widget_index < 9)
            $this->widgets[$widget_index++]->echo_search_item($search_data);
        echo "</table>$table_header";
        while($widget_index < $widget_count)
            $this->widgets[$widget_index++]->echo_search_item($search_data);
        echo "
            </table>
            <div class='center-align' style='clear: both;'></div>
            <input type='submit' value='", attr_safe(_("Search")), "'>
            <input type='button' onclick=\"window.location.assign('{$_SERVER['PHP_SELF']}?show=blank_search_form');\" value='", attr_safe(_("Clear form")), "'>
            </form>";
        echo get_search_configure_button("show=search_form");
        echo "</div>";
        echo "<p>
            "._("For terms that you type in, matching is case-insensitive and unanchored; so, for instance, 'jim' matches both 'Jimmy Olsen' and 'piggyjimjams'. This doesn't apply to PG etext numbers, for which you should type in the complete number.")."
            </p><p>
            "._('"(list ok)": You can search by multiple ProjectIDs or PG etext numbers at once: enter the list of ProjectIDs or PG etext numbers, separated by commas, semicolons, or spaces.')."
            </p><p>
            "._('If desired, you can select multiple values for Language, Genre, Difficulty, Special Day, or State (e.g., by holding down Ctrl).')."
        </p>
        ";
    }

    private function get_widget_contribution($search_data)
    {
        $condition = '1';
        foreach ( $this->widgets as $widget )
        {
            $contribution = $widget->get_sql_contribution($search_data);
            if ( $contribution == '' )
                continue;
            $condition .= "\nAND $contribution";
        }
        return $condition;
    }

    public function get_condition($show_view)
    {
        if(($show_view == 'p_search') && !$_POST)
        {
            $condition = array_get($_SESSION, 'search_condition', "1");
        }
        else // (p_search && have POST) or search with GET data
        {
            // Construct the search query.
            $condition = $this->get_widget_contribution($_REQUEST);
            // save the condition to use for paging or changing configuration or sorting
            $_SESSION['search_condition'] = $condition;
            // save the POST data to use to initialise the search form if refining
            $_SESSION['search_data'] = $_REQUEST;
        }
        return $condition;
    }
}

function escape_value($value)
{
    return mysqli_real_escape_string(DPDatabase::get_connection(), $value);
}
