<?php
/**
 * Wordpress Form Builder Utility Class
 * 
 * A group of classes designed to make it easier and quicker to create forms 
 * within wordpress plugins for the admin section. Using this class should hopefully 
 * reduce development and debugging time.
 * 
 * This code is very much in alpha phase, and should not be distributed with plugins 
 * other than by Dan Harrison.
 * 
 * @author Dan Harrison of WP Doctors (http://www.wpdoctors.co.uk)
 *
 * Version History
 * 
 * V0.01 				 - Initial version released.
 * 
 * V0.02 				 - Added support for uploading files.
 * 
 * V0.03 				 - Added support for submission checking and form validation.
 * 
 * V0.04 				 - Added checkbox list support. Added section break code.
 * 
 * V0.05 				 - Added checking for when multiple forms are on the same page.
 * 
 * V0.06 - 30th Oct 2010 - Added isset check for $_POST field to avoid warning messages.
 * 
 * V0.07 - 23rd Nov 2010 - Added method to override the form action attributed if required.
 * 
 * V0.08 -  1st Dec 2010 - Added ability to show or hide the required field labels.
 * 						 - Fixed issue with slashes and trim when retrieving data from $_POST.
 * 
 * V0.09 - 30th Mar 2011 - Fixed issue with validating field types of uploadfile.
 * 						 - added getValue() method to get a single form value.
 * 
 * V0.10 - 14th Apr 2011 - Added support for splitting checkbox lists into columns.
 * 					     - Added id=formname if a form name is specified.
 * V0.11 - 15th Jun 2011 - Fixed issue with select and empty arrays.
 * V0.12 - 23rd Jun 2011 - Added support to determine which button was pressed.  
 * V0.13 - 29th Jun 2011 - Added ability to have a mixture of fields on a single line.
 * V0.14 -  8th Jul 2011 - Added support for highlighting rows with errors.
 * V0.15 - 12th Jul 2011 - Added support for complex validation of fields.
 * V0.16 - 17th Jul 2011 - Added validation for multiple items in checklists.
 * V0.17 -  9th Nov 2011 - Added support for getting element type using hash list of
 * 						   field names for efficiency.
 * V0.18 - 30th Nov 2011 - Added support for radio buttons in form.
 * V0.19 -  6th Mar 2012 - Added support for adding break to start of a form.
 * V0.20 - 18th Apr 2012 - Added methods: 
 * 						 - Element: setCSSClass(), setCustomHTML()
 * 						 - Form: setElementHTML(), setElementCSSClass(), setElementCustomHTML()
 * V0.21 - 23rd May 2012 - Added more checks to upload file field.
 * V0.22 - 23rd Aug 2012 - Added improved support for existing files on file upload fields. 
 * V0.23 - 13th Dec 2012 - Added array checking for minlen, maxlen.
 * V0.24 -  1st Apr 2013 - Added support for changing how many columns to use for the checkbox lists.
 * V0.25 -  9th May 2013 - Added reference to parent object to add translatable strings.
 * V0.26 -  1st Sep 2013 - Added secondary button class for adding buttons to form.
 * V0.27 - 21st Nov 2013 - Added support for suffix items. 
 * 						 - Added class information to field tr rows using form name and field name.
 * 						 - Added support for arrays in getArrayValue().
 * 						 - Added class names for checkboxlist checkbox fields.
 * V0.28 - 13th Feb 2017 - Fixed constructor for PHP7.
 * V0.29 - 11th Apr 2018 - Added HTML attributes support (e.g. for placeholders, data attributes)
 * V0.30 - 15th Jan 2019 - [MJ] Added support for numeric fields and settings pages
 */						 


/**
 * Indicates if we have a break at the start of a form.
 */
if (!defined('FORM_BUILDER_START_OF_FORM')) {
	define('FORM_BUILDER_START_OF_FORM', '_____START_OF_FORM_____');
}

/**
 * Class that represents a HTML form for the Wordpress admin area.
 */
if (!class_exists('FormBuilder')) { class FormBuilder {

	/**
	 * A list of the elements to go in the HTML form. 
	 * @var Array
	 */
	private $elementList;
	
	/**
	 * A list of the names of the elements to go in the HTML form. 
	 * @var Array
	 */
	private $elementListNames;
	
	/**
	 * A list of the elements of where a break is needed.
	 * @var Array
	 */
	private $breakList;
	
	/**
	 * The form name, used for the name attribute of the form.
	 * @var String The name of the form.
	 */
	private $formName;
	
	/**
	 * A list of the buttons to go in the HTML form. 
	 * @var Array
	 */
	private $buttonList;

    /**
     * MJ 2019-01-15 - Added support for ajax buttons
     *
     * A list of the ajax buttons to go in the HTML form.
     * These buttons will not trigger a page submit, so we should
     * hold them in a separate array to the normal buttons
     * @var Array
     */
    private $ajaxButtonList;

    /**
	 * The text used on the submit button.
	 * @var String The text used on the submit button.
	 */
	private $submitlabel;	
	
	/**
	 * A list of the errors that have occured for this form.
	 * @var Array A list of errors with this form.
	 */
	private $errorList;
	
	/**
	 * The URL to use for the action attribute in the form, rather than the current page.
	 * @var String The URL for the action form attribute.
	 */
	private $actionURL;
	
	/**
	 * If true, show the required labels. If false, hide the required labels.
	 * @var Boolean If true, show required labels.
	 */
	private $showRequiredLabels;
	
	/**
	 * Internal list of translation strings.
	 * @var String;
	 */
	private $translationStrings;


    /**
     * Is the current form a settings page
     * @var boolean
     */
	private $settingsPage = false;
	
	/**
	 * Constructor
     * @param string|boolean $name The name of the form
	 */
	function __construct($name = false)
	{
		$this->elementList = array();
		$this->elementListNames = array();
		$this->buttonList = array();
		$this->setSubmitLabel(false);
		$this->formName = $name;
		$this->errorList = array();
		$this->breakList = array();
		
		$this->showRequiredLabels = true;
		
		// The default translation strings.
		$this->translationStrings = array(
			"Please fill in the required '%s' field." 	=> '',
			"There's a problem with value for '%s'." 	=> '',
			'required' 									=> ''
		);
	}

    /**
     * MJ 2019-01-15 - Set the current form as a settings page
     *
     * @since V0.05
     * @param boolean $is_settings Whether the current form is a settings form
     */
	public function setSettingsPage($is_settings)
    {
        $this->settingsPage = $is_settings;
    }

	/**
	 * Set the label for the submit button to the specified text. If the specified label is blank, 
	 * then "Update Settings" is used as a default.
	 * 
	 * @param string|boolean $label The text to use for the submit button.
	 */
	function setSubmitLabel($label)
	{
		// Only update if $label is a valid string, otherwise set default.
		if ($label)
			$this->submitlabel = $label;
		else 
			$this->submitlabel = __('Update Settings');
	}
	
	
	/**
	 * Set the URL for the action attribute for the form.
	 * @param string $url The URL for the action attribute for the form. If false, then current page is used.
	 */
	function setActionURL($url) {
		$this->actionURL = $url;
	}
	
	
	/**
	 * Enable or disable showing the required field labels.
	 * @param boolean $boolean If true, show the required field labels, false otherwise.
	 */
	function setRequiredLabelsVisible($boolean) {
		$this->showRequiredLabels = $boolean;
	}
	
	
	/**
	 * Add the specified form element to the internal list of elements to put on the form.
	 * @param FormElement $formElement <code>FormElement</code> object to add to the form.
     * @return boolean
	 */
	function addFormElement($formElement) {
		if (!$formElement) {
			return false;
		}
		array_push($this->elementList, $formElement);
		
		// Add name addFormElementof element, linked to it's object
		$this->elementListNames[$formElement->name] = $formElement;
		
		// Add reference to parent.
		$formElement->parent = $this;
		return true;
	}
	
	/**
     * Add a button to be added to the end of the form.
	 * @param String $buttonName The name of the button.
	 * @param String $buttonText The text to be used for the button itself.
	 */
	function addButton($buttonName, $buttonText) {
	    $this->buttonList[$buttonName] = $buttonText;
	}

    /**
     * MJ 2019-01-15 - Added support for WP loading spinner
     * Add an ajax button to be added to the end of the form.
     *
     * @param String $buttonName The name of the button.
     * @param String $buttonText The text to be used for the button itself.
     */
    function addAjaxButton($buttonName, $buttonText) {
        $this->ajaxButtonList[$buttonName] = $buttonText;
    }


	/**
	 * Add a break at the current position in the form where form fields are being added. 
	 * If no form elements have been added, break is added at start of form.
	 * 
	 * @param String $sectionID The string to use as the section ID for the section we've created.
	 * @param String $prefixHTML The HTML to add before the section break if specified.
	 */
	function addBreak($sectionID, $prefixHTML = false)
	{
		// Get the latest element to have been added to the array		
		$latestElement = end($this->elementList);
				
		// Nowhere to add a break
		if ($latestElement === FALSE) {
			$elemName = FORM_BUILDER_START_OF_FORM;
		} else {
			$elemName = $latestElement->name;
		}
		
		// Somewhere to add a break, so use form field name
		// as a pointer of where to add break
		$this->breakList[$elemName] = array('sectionid' => $sectionID, 
											'prefixHTML' => $prefixHTML
											); 
	}
	
	
	/**
	 * Determine if one of the fields in this form is an upload file field.
	 * @return Boolean True if there is a file upload field, false otherwise.
	 */
	function haveFileUploadField()
	{
		$haveUploadField = false;
		foreach ($this->elementList as $element)
		{
			if ($element->type == 'uploadfile') {
				$haveUploadField = true;
				break;
			}
		}
		
		return $haveUploadField;
	}
	
	
	/**
	 * Generates the HTML for the form object.
	 * @return String The HTML for this form object.
	 */
	function toString()
	{
		// Start main form attributes
		$formAttributes = array();
		$formAttributes['method'] = 'POST';	
		
		// Use custom action attribute?
		if ($this->actionURL) {
			$formAttributes['action'] = $this->actionURL;
		} else {
			// Current page
			$formAttributes['action'] = str_replace( '%7E', '~', $_SERVER['REQUEST_URI']);
		}
		
		// Add the form name if specified
		$namestring = '';
		if ($this->formName) {
			$formAttributes['name'] = $this->formName;	
			$formAttributes['id'] = $this->formName;
		}
		
		// Need extra attribute if there's a upload field
		if ($this->haveFileUploadField()) {
			$formAttributes['enctype'] = 'multipart/form-data';
		}
		
		// Render form with all attributes
		$attributeString = false;
		foreach($formAttributes as $name => $value) {
			$attributeString .= sprintf('%s="%s" ', $name, $value);
		}
		
		// Start form
		$resultString = "\n<form $attributeString>\n";
		
		// Is first item a break? If so, render it.
		if (isset($this->breakList[FORM_BUILDER_START_OF_FORM])) {
			$resultString .= $this->createTableHeader(array('id' => $this->breakList[FORM_BUILDER_START_OF_FORM]['sectionid']), $this->breakList[FORM_BUILDER_START_OF_FORM]['prefixHTML']);
		} else {
			$resultString .= $this->createTableHeader();
		}		
		
		// Now add all form elements
		foreach ($this->elementList as $element)
		{
			// Hidden elements are added later
			if ($element->type == 'hidden') {
				continue;
			}
			
			// Render form element				
			$resultString .= $element->toString($this->showRequiredLabels);

			// Add section breaks if this element is in the break list.
			// Add break after element HTML
			if (in_array($element->name, array_keys($this->breakList)))
			{
				$resultString .= $this->createTableFooter();
				$resultString .= $this->createTableHeader(array('id' => $this->breakList[$element->name]['sectionid']), $this->breakList[$element->name]['prefixHTML']);
			}
			
		}
		
		$resultString .= $this->createTableFooter();
		
		// Button area
		$resultString .= '<p class="submit">'."\n";
		
		// Add submit button
		$resultString .= "\t".'<input class="button-primary" type="submit" name="Submit" value="'.$this->submitlabel.'" />'."\n";
		
		// Add remaining buttons
		foreach ($this->buttonList as $buttonName => $buttonLabel) {
			$resultString .= "\t<input type=\"submit\" class=\"button-secondary\" name=\"$buttonName\" value=\"$buttonLabel\" />\n";		
		}

        // MJ 2019-01-15 - Added Ajax buttons
		if (!empty($this->ajaxButtonList))
		{
            foreach ($this->ajaxButtonList as $buttonName => $buttonLabel) {
                $resultString .= "\t<input type=\"submit\" class=\"button-secondary button-ajax\" name=\"$buttonName\" value=\"$buttonLabel\" />\n";
                $resultString .= "\t<span class=\"spinner\"></span>";
            }
		}

		// Hidden field to indicate update is happening
		$resultString .= sprintf("\t".'<input type="hidden" name="update" value="%s" />'."\n", $this->formName);
				
		// Add any extra hidden elements
		foreach ($this->elementList as $element)
		{
			// Leave all hidden elements until the end.
			if ($element->type == 'hidden') {	
				$resultString .= "\t".'<input type="hidden" name="'.$element->name.'" value="'.$element->value.'" />'."\n";
			}
		}		
		
		$resultString .= '</p>'."\n";

        // MJ 2019-01-15 - Added support for settings pages
        if ($this->settingsPage === true) {
            ob_start();
            settings_fields( 'WPUC_showPage_Settings' );

            // No need to do the settings sections, these fields are rendered by the form class
            // left it commented here in case we need it in the future
            // do_settings_sections( 'WPUC_showPage_Settings' );
            $resultString .= ob_get_clean();
        }

		// End form
		$resultString .= "\n</form>\n";
		
		return $resultString;
	}
	
	/**
	 * Return string to start a HTML table.
	 * @return String HTML to start a HTML table.
	 * @return String The HTML to put before the HTML table if specified.
	 */	
	private function createTableHeader($attributeList = false, $prefixHTML = false)
	{
		// Render table with all specified attributes
		$attributeString = false;
		if ($attributeList)
		{
			foreach($attributeList as $name => $value) {
				$attributeString .= sprintf('%s="%s" ', $name, $value);
			}
		}		
		
		return "$prefixHTML\n\n<table class=\"form-table\" $attributeString>\n";
	}
	
	/**
	 * Return string to terminate a HTML table.
	 * @return String HTML to terminate a HTML table.
	 */
	private function createTableFooter() {
		return "</table>\n";
	}
	
	/**
	 * Determine if the form has been submitted
	 * @return Boolean True if the form has been submitted, false otherwise.
	 */
	function formSubmitted()
	{
		// Do we have a form name? If so, if we have this form name, then this
		// particular form has been submitted.
		if ($this->formName) {
			return (isset($_POST['update']) && $_POST['update'] == $this->formName);	
		} 
		// No form name, just detect our hidden field.
		else {
			return (isset($_POST['update']));
		}
	}
	
	/**
	 * Get the label text of the button that was clicked.
	 * @return String The label for the button.
	 */
	function getClickedButton()
	{
		if (!$this->formSubmitted()) {
			return false;
		}
		
		// Was the main button pressed? Return the label
		if (isset($_POST['Submit'])) {
			return $_POST['Submit'];
		}
		
		// Not the main button, one of the extra buttons?
		if (!empty($this->buttonList)) 
		{
			foreach ($this->buttonList as $buttonName => $buttonText) 
			{
				if (isset($_POST[$buttonName])) {
					return $buttonText;
				}
			}
		}
		
		return false;
	}
	
	
	/**
	 * Get the list of errors following a form validation.
	 * @return Array The list of errors.
	 */
	function getListOfErrors() {
		return $this->errorList;
	}
	
	/**
	 * Determine if the form is valid
	 * @return Boolean True if the form is valid, false otherwise. False is also returned if the form has not been submitted.
	 */
	function formValid()
	{
		// Not submitted, so can't be valid.
		if (!$this->formSubmitted())
			return false;
						
		// Empty error list
		$this->errorList = array();
						
		// Check each field is valid.
		foreach ($this->elementList as $element)
		{
			// Fire off the element code that extracts all of the data from this array.
			$element->extractValuesFromArray($_POST);			
			
			// Validate the element
			if (!$element->isValid())
			{
				// Add error to internal list of errors for this form.
				$this->errorList[] = $element->getErrorMessage();
			}
			
			// Check any suffix items for each element
			else 
			{
				// Handle suffix items last, to see if they are valid		
				if (!empty($element->suffixItems))
				{
					// List of positions => elements
					foreach ($element->suffixItems as $position => $elementList)
					{
						
						// Each object in this position...
						foreach ($elementList as $elementItem)
						{
							if (!$elementItem->isValid())
							{
								// Add error to internal list of errors for this form.
								$this->errorList[] = $elementItem->getErrorMessage();
							}
						} 
					} // end foreach
				} // end if
			}
		}
		
		// If we have errors, clearly the form is not valid
		return (count($this->errorList) == 0); 
	}
	
	/**
	 * Simple safe function to get an array value first checking it exists.
	 * @param Array array The array to retrieve a value for.
	 * @param String $key The key in the array to check for.
	 * @return String The array value for the specified key if it exists, or false otherwise.
	 */
	function getArrayValue($array, $key)
	{
		if (isset($array[$key]))
		{
			// Value is an array...
			if (is_array($array[$key]))
			{
				// Check each value in array is free of slashes.
				foreach ($array[$key] as $innerKey => $innerValue)
				{
					// Recursively clear slashes
					$array[$key][$innerKey] = $this->getArrayValue($array[$key], $innerKey);
				}
				return $array[$key];
			}
			
			// Simple string.
			else {
				return trim(stripslashes($array[$key]));
			}
			
		}
		return false;
	}
	
	/**
	 * Get the values for this submitted form.
	 * @param Array $elementList If specified, return just the values for these elements. If false, return all values for this form.
	 * @return Array A list of all the submitted field names => values for this form.
	 */
	function getFormValues($selectList = false)
	{
		if (!$this->formSubmitted())
			return false;
			
		$returnList = array();
		foreach ($this->elementList as $element)
		{
			// Don't return custom types
			if ($element->type == 'custom') {
				continue;
			}				
			
			if ($selectList && is_array($selectList))
			{
				// Only add if in the list of specified elements.
				if (in_array($element->name, $selectList)) {			
					$returnList[$element->name] = $element->value;
				}
			} 
			// Add as a raw value as there is no list to choose from.
			else {												
				$returnList[$element->name] = $element->value;
			}
			
			// See if there are any values from suffix list.
			if ($suffixItemValues = $element->getSuffixValues())
			{
				foreach ($suffixItemValues as $subItemName => $subItemValue) {
					$returnList[$subItemName] = $subItemValue;
				}
			}
			
		} // end of foreach
		
		return $returnList;
	}
	
	/**
	 * Get a single value for the specified field.
	 * @param String $elemName The name of the field to get the value for.
	 * @return String The value of the field.
	 */
	function getValue($elemName)
	{
		if (isset($this->elementListNames[$elemName])) {
			return $this->elementListNames[$elemName]->value;
		}
		
		return FALSE;
	}
	

	/**
	 * Get the type of the element in this form.
	 * @param String $elemName The name of the form element.
	 * @return String The type of this form element, or false if the element wasn't found.
	 */
	function getElementType($elemName)
	{
		if (isset($this->elementListNames[$elemName])) {
			return $this->elementListNames[$elemName]->type;
		}
		
		return FALSE;
	}	
	
	
	
	/**
	 * Function that sets the HTML of the element to that specified.
	 * 
	 * @param String $elemName The name of the element to update.
	 * @param String $html The HTML to use for the element.
	 */
	function setElementHTML($elemName, $html)
	{
		// Only update if element is custom
		if (isset($this->elementListNames[$elemName]) && $this->elementListNames[$elemName]->type == 'custom') {
			$this->elementListNames[$elemName]->setCustomHTML($html);
		}
	}	
	
	/**
	 * Function that sets the HTML of the element to that specified, and converts it to a custom HTML element.
	 * 
	 * @param String $elemName The name of the element to update.
	 * @param String $html The HTML to use for the element.
	 */
	function setElementCustomHTML($elemName, $html)
	{
		// Only update if element is custom
		if (isset($this->elementListNames[$elemName])) {
			$this->elementListNames[$elemName]->setTypeAsCustom($html);
		}
	}	
	
	/**
	 * Function that sets the description of the element to that specified.
	 * 
	 * @param String $elemName The name of the element to update.
	 * @param String $html The HTML to use for the description.
	 */
	function setElementDescription($elemName, $html)
	{
		// Only update if element is custom
		if (isset($this->elementListNames[$elemName])) {
			$this->elementListNames[$elemName]->description = $html;
		}
	}
	
	
	/**
	 * Function that sets the CSS class of the element to that specified.
	 * 
	 * @param String $elemName The name of the element to update.
	 * @param String $cssClass The css class(es) to set the element to.
	 */
	function setElementCSSClass($elemName, $cssClass)
	{
		if (isset($this->elementListNames[$elemName])) {
			$this->elementListNames[$elemName]->setCSSClass($cssClass);
		}
	}	
	
	
	/**
	 * Get a list of the elements in this form.
	 * @return Array The list of elements in the form.
	 */
	function getListOfElements()
	{
		return $this->elementListNames;
	}
	
	
	/**
	 * Set the default values for this form.
	 * @param Array $valueList The list of field name => field value pairs.
	 */
	function setDefaultValues($valueList)
	{
		if (!$valueList) {
			return;
		}				
				
		// Iterate through form fields checking if there's a default value to
		// use, because we don't have an associative list of elements
		foreach ($this->elementList as $element)
		{						
			// Do we have a default value for this field?
			if (isset($valueList[$element->name])) {
				$element->setValue($valueList[$element->name]);								
			}
			
			// Handle elements with a suffix list. 
			if (!empty($element->suffixItems))
			{
				// List of positions => elements
				foreach ($element->suffixItems as $position => $elementList)
				{
					// Each object in this position...
					foreach ($elementList as $elementItem)
					{						
						$elementItem->setValue($this->getArrayValue($valueList, $elementItem->name));
					} 
				} // end foreach 
			} // end if
		} //end foreach
	}
	
	/**
	 * Get the translation string.
	 * @param String $str The string to translate.
	 * @return String The translated string.
	 */
	function getTranslationString($str) {
		return $this->getArrayValue($this->translationStrings, $str);
	}
	
	
	/**
	 * Set all of the strings for the translation list.
	 * @param Array $list The list of strings to replace the internal list with.
	 */
	function setAllTranslationStrings($list) {
		$this->translationStrings = $list;
	}
	
	/**
	 * Returns the form name.
	 * @return String The form name.
	 */
	function getFormName() {
		return $this->formName;
	}
}


/**
 * Class that represents a HTML form element for the Wordpress admin area.
 */
class FormElement {
	
	/**
	 * The parent form.
	 * @var FormBuilder
	 */
	public $parent;
	
	/**
	 * The different types of form element, including <code>select</code>, <code>text</code>, 
	 * <code>checkbox</code>, <code>hidden</code> and <code>textarea</code>.  
	 *    
	 * @var String The type of the form element.
	 */
	public $type = 'text';
	
	/**
	 * The current value of this form element.
	 * @var String The current value of this form element.
	 */
	public $value;
	
	/**
	 * The label for this form element.
	 * @var String The descriptive label for this form element.
	 */
	public $label;
	
	/**
	 * The <code>name</code> of the form element, as in the HTML attribute name.
	 * @var String The HTML attribute name of this element.
	 */
	public $name;
	
	/**
	 * The description of this form element, that typically goes after the element.
	 * @var String The description of this form element.
	 */
	public $description;
	
	/**
	 * Boolean flag to determine if the field is a form field (which if true, automatically adjusts the entry field to fit the screen size)
	 * @var Boolean True if this is a form field, false otherwise.
	 */
	public $isformfield = true;
	
	/**
	 * The number of rows to use in a text area.
	 * @var Integer the number of rows to use in a text area.
	 */
	public $textarea_rows = 4;

	/**
	 * The number of columns to use in a text area.
	 * @var Integer the number of columns to use in a text area.
	 */	
	public $textarea_cols = 70;
	
	/**
	 * The maximum length of a field.
	 * @var Integer The maximum length of a field, set to 0 by default (no limit).
	 */
	public $text_maxlen = 0;

    /**
     * Minimum number value of a field
     * @var Float The minimum length of the field, set to null by default (no limit);
     */
	public $number_min = null;

    /**
     * Maximum number value of a field
     * @var Float The maximum length of the field, set to null by default (no limit);
     */
	public $number_max = null;

    /**
     * Step value for number fields, the amount will increase or decrease by this value each time
     * @var Float The step amount for + and - buttons
     */
	public $number_step = 1;

    /**
     * The minimum date value for date fields
     * @var DateTime The minimum date value
     */
	public $date_min = null;

    /**
     * The maximum date value for date fields
     * @var DateTime The maximum date value
     */
	public $date_max = null;

	/**
	 * The list of items used in an HTML select box.
	 * @var Array
	 */
	public $seleu_itemlist;
	
	/**
	 * The label for a checkbox.
	 * @var String The text that goes next to a checkbox.
	 */
	public $checkbox_label;
	
	/**
	 * The CSS class to set the HTML form element to.
	 * @var String The CSS class to set teh HTML form element to.
	 */
	public $cssclass;
	
	/**
	 * HTML rendered after the form element, but before the description.
	 * @var String The HTML used to go after the form element. 
	 */
	public $afterFormElementHTML;	

	/**
	 * HTML used to create a custom form element.
	 * @var String The HTML to create a custom form element.
	 */
	private $customHTML = false;
	
	/**
	 * Is this form value required? 
	 * @var Boolean True if required, false if otherwise.
	 */
	public $required = false;
	
	/**
	 * The message to show if there's something wrong with this error message.
	 * @var String The error message.
	 */
	public $errorMessage;
	
	/**
	 * Function that validate this data field.
	 * @var Function Reference to a function used to validate this data field.
	 */
	public $validationFn;
	
	/**
	 * The list of sub elements to be shown within the form row.
	 * @var Array The list of elements.
	 */
	public $subElementList;
	
	
	/**
	 * Does the element have an error? If so, add CSS to show that.
	 * @var Boolean
	 */
	public $renderWithErrors = false;
	
	/**
	 * The list of validation rules to use when the element gets validated.
	 * @var Array
	 */
	protected $validationRules;
	
	/**
	 * Used for file uploads. If shown, shows the existing file that was uploaded.
	 * @var Boolean
	 */
	protected $showExistingFile;
	
	/**
	 * Used for file uploads. If the field has a value, then the field is valid. Also 
	 * valid if there's a file being uploaded.
	 * 
	 * @var Boolean
	 */
	protected $validIfValue;
	
	
	/**
	 * The number of columns to have for checkbox lists.
	 * @var Integer
	 */
	public $checkboxListCols = 3;
	
	
	/**
	 * The list of suffix objects to render after a form field (or after elements of a form field in the case of radio buttons).
	 * @var Array
	 */
	public $suffixItems = array();
	
	/**
	 * Stores a list of keys => values for the attributes for this form element. 
	 * @var array 
	 */
	public $attributes = array();


    /**
     * Is this field being rendered as part of a settings page
     * @var boolean
     */
	public $isSetting = false;
	
	/**
	 * Constructor
	 */
	function __construct($name, $label, $required = false)
	{
		$this->name  = $name;
		$this->label = $label;		
		$this->required = $required;
	}

    /**
     * MJ 2019-01-15 - Added support for settings pages
     * Change the isSetting status of this element.
     * @since V0.30
     * @param boolean $isSetting
     */
	public function setIsSetting($isSetting)
    {
        $this->isSetting = $isSetting;
    }

	/**
	 * Sets this element to be a checkbox.
	 */
	function setTypeAsCheckbox($labeltext = false)
	{
		$this->type = 'checkbox';
		$this->checkbox_label = $labeltext;
		
		// Formfield doesn't work if a checkbox
		$this->isformfield = false;
	}
	
	
	/**
     * MJ 2019-01-15 - Added support for wysiwyg editors
     *
     * Set the type of this element to be a wysiwyg editor
     * @since V0.30
	 */
	function setTypeAsWysiwyg() {
		$this->type = 'wysiwyg';
	}

    /**
     * Set the type of this element to be a text area with the specified number of rows and columns.
     * @param Integer $rows The number of rows for this text area, the default is 4.
     * @param Integer $cols The number of columns for this text area, the default is 70.
     */
    function setTypeAsTextArea($rows = 4, $cols = 70) {
        $this->type = 'textarea';
        $this->textarea_cols = $cols;
        $this->textarea_rows = $rows;
    }


    /**
     * MJ 2019-01-15 - Added support for numerical inputs
     * Set the type of this element to be a input box accepting numerical values
     *
     * @since V0.30
     * @param Integer $min The minimum value this input accepts.
     * @param Integer $max The maximum value this input accepts.
     * @param Float $step The amount to change the field with + and - buttons
     */
    function setTypeAsNumber($min = null, $max = null, $step = 1.0) {
        $this->type = 'number';
        $this->number_min = $min;
        $this->number_max = $max;
        $this->number_step = $step;
    }

    /**
     * MJ 2019-01-15 - Added support for date inputs
     * Set the type of this element to be a date input element
     *
     * @since V0.30
     * @param boolean $time Should this field also include a time entry element
     * @param DateTime $min The minimum value for this field
     * @param DateTime $max The maximum value for this field
     */
    function setTypeAsDate($time = true, $min = null, $max = null)
    {
        if ($time) {
            $this->type = 'datetime-local';
        } else {
            $this->type = 'date';
        }

        if ($min && $min instanceof DateTime) {
            $this->date_min = $min;
        }

        if ($max && $max instanceof DateTime) {
            $this->date_max = $max;
        }
    }

	/**
	 * Sets this element to be a hidden element.
	 */
	function setTypeAsHidden() {
		$this->type = 'hidden';
	}
	
	/**
	 * Sets the type to be static, where the value is used rather than a normal form field.
	 */
	function setTypeAsStatic() {
		$this->type = 'static';
	}
	
	/**
	 * Sets the type to be a file upload form, where a uploader box is used rather than a normal form field.
	 * @param Boolean $showExistingFile If true, then show an extra line that shows the existing filename.
	 * @param Boolean $validIfValue If true, then the field validates if true if a value is set in $_POST or a file is uploaded.
	 */
	function setTypeAsUploadFile($showExistingFile = false, $validIfValue = false) 
	{
		$this->showExistingFile = $showExistingFile;		
		$this->validIfValue 	= $validIfValue;
		$this->type 			= 'uploadfile';
	}	
		
	/**
	 * Sets the element type to be a combo box (A SELECT element in HTML). The specified list of 
	 * items can be a simple list (e.g. x, y, z), or a list of values mapping to a description 
	 * (e.g. a => 1, b => 2, c => 3). However, in the case of a simple list, the values will be 
	 * interpreted as their actual index e.g. (0 => x, 1 => y, 2 => z). If the value of this element
	 * matches one of the options in the list, then that option will be selected when the HTML is rendered.
	 * 
	 * @param Array $itemList The list of items to set in the combo box.
	 */
	function setTypeAsComboBox($itemList) {
		$this->type = 'select';
		$this->seleu_itemlist = $itemList;
	}
	
	/**
	 * Sets the element type to be a radio button group. The specified list of 
	 * items can be a simple list (e.g. x, y, z), or a list of values mapping to a description 
	 * (e.g. a => 1, b => 2, c => 3). However, in the case of a simple list, the values will be 
	 * interpreted as their actual index e.g. (0 => x, 1 => y, 2 => z). If the value of this element
	 * matches one of the options in the list, then that option will be selected when the HTML is rendered.
	 * 
	 * @param Array $itemList The list of items to set in the radio button list.
	 */
	function setTypeAsRadioButtons($itemList) {
		$this->type = 'radio';
		$this->seleu_itemlist = $itemList;
	}	
	
	/**
	 * Sets the element type to be a checkbox list. The specified list of items can be a simple list 
	 * (e.g. x, y, z), or a list of values mapping to a description (e.g. a => 1, b => 2, c => 3). 
	 * However, in the case of a simple list, the values will be interpreted as their actual index 
	 * e.g. (0 => x, 1 => y, 2 => z). If any of the values are marked as on, then that checkbox will
	 * be ticked when the HTML is rendered.
	 * 
	 * @param Array $itemList The list of items to create tickboxes for.
	 */
	function setTypeAsCheckboxList($itemList) {
		$this->type = 'checkboxlist';
		$this->seleu_itemlist = $itemList;
	}	

	
	/**
	 * Sets this element to be a custom element using the specified HTML to create a form field.
	 */
	function setTypeAsCustom($HTML) {
		$this->type = "custom";
		$this->customHTML = $HTML;
	}
	
	
	/**
	 * Set this element to be a row featuring multiple form elements.
	 * @param Array $elementList The list of sub-elements.
	 */
	function setTypeAsMergedElements($elementList) {
		$this->type = "merged";
		$this->subElementList = $elementList;
	}
	
	
	/**
	 * Set the internal value variable to the specified parameter.
	 * @param Mixed $elementValue The value to set the form element to.
	 */
	function setValue($elementValue) 
	{
		// Set the value of the individual merged elements.
		if ($this->type == 'merged')
		{
			if (!empty($this->subElementList))
			{
				// Check each sub element we have, and check it
				// for a default value in the passed argument.
				foreach ($this->subElementList as $subElem)
				{
					if (isset($elementValue[$subElem->name])) {
						 $subElem->value = $elementValue[$subElem->name];
					}
				}
			}
			
			// Also add the data to the merged list
			$this->value = $elementValue;
		}
		else {
			$this->value = $elementValue;
		}
	}
	
	
	/**
	 * Set the custom HTML of this element to specified parameter.
	 * @param String $html The new HTML for this element.
	 */
	function setCustomHTML($html) {
		$this->customHTML = $html;
	}
	
	
	/**
	 * Set the CSS class(es) for this element to the specified parameter. 
	 * @param String $cssClass The new css classes for this element.
	 */
	function setCSSClass($cssClass) {
		$this->cssclass = $cssClass;
	}
	
	
	/**
	 * Render the current form element as an HTML string.
	 * 
	 * @param Boolean $showRequiredLabel If true, show the required label.
	 * @param Boolean $showAsMergedField If true, then this element is being rendered as a sub element.
     * 
	 * @return String This form element as an HTML string.
	 */
	function toString($showRequiredLabel = false, $showAsMergedField = false) 
	{
		$elementString = false;
		
		// Determine if there's an error or not, allow CSS to show that.
		$errorcss = false;
		if ($this->renderWithErrors) {
			$errorcss = "row-has-errors";
		}
		
		// Formfield class, on by default
		$trclass = "form-field $errorcss";
		if (!$this->isformfield) {
			$trclass = $errorcss;
		}
		
		// Don't need rows for merged fields
		if (!$showAsMergedField) 
		{
			// Get the parent form, ensure we have a valid name to use.
			$formName = $this->parent->getFormName();
			if (!$formName) {
				$formName = 'wpform';
			}
			
			// Create a row, adding a class using the form name, name of the field, and _tr.
			$elementString = sprintf('<tr valign="top" class="%s %s_%s_tr">', $trclass, $formName, $this->name)."\n";
		}
		
		// Provide the option of showing the required field.
		$requiredHTML = false;
		if ($showRequiredLabel && $this->required) {
			$requiredHTML = '<span class="description req"> (' . $this->getTranslationString('required') .')</span>';
		}

		// The label - if a normal row, this is just a table heading
		if (!$showAsMergedField) 
		{
			$elementString .= "\t".'<th scope="row"><label for="'.$this->name.'">'.$this->label.$requiredHTML.'</label></th>'."\n";		 
		
			// Start the table data for the form element and description 
			$elementString .= "\t<td>\n\t\t";
		}
		// Showing as merged field
		else {
			// Only show a label if we have one.
			if ($this->label) {
				$elementString .= "\t<div class=\"subelement-title\"><b>$this->label$requiredHTML</b></div>\n";				
			}
		}

		// Have we got any details on the maximum string length?
		$sizeInfo = false;
		if ($this->type == 'textarea'  && 						// Text Area only
			$this->validationRules && 							// Have validation rules
			isset($this->validationRules['maxlen']) && 			// Have a maximum length
			$maxLen = ($this->validationRules['maxlen'] + 0)) 	// And it's greater than 0
		{
			$sizeInfo = 'textarea_counter';

			// Hide size counter when not in use.
			$this->afterFormElementHTML = '<span id="'. $this->name .'_count" class="character_count_area" style="display: none">
												<span class="max_characters" style="display: none">'.$maxLen.'</span>
												<span class="count_field"></span> characters remaining
											</span>' . $this->afterFormElementHTML; 
		}
		
		// Build CSS information
		$elementClass = "class=\"$this->cssclass $sizeInfo\"";
		$elementID    = "id=\"$this->name\"";
		
		// Build attributes as a string to include in fields.
		$attributeString = false;
		if (!empty($this->attributes)) 
		{
		    foreach ($this->attributes as $key => $value) {
		        $attributeString .= sprintf(' %s="%s"',$key, $value);
		    }
		}

		// The actual form element
		switch ($this->type)
		{
			case 'select':
				$elementString .= "<select name=\"$this->name\" $elementClass $elementID $attributeString>";
				if (!empty($this->seleu_itemlist))
				{
					foreach ($this->seleu_itemlist AS $value => $label)
					{
						
						$htmlselected = "";
						if ($value == $this->value) {
							$htmlselected = ' selected="selected"';						
						}
						
						$elementString .= "\n\t\t\t";
						$elementString .= '<option value="'.$value.'"'.$htmlselected.'>'.$label.'&nbsp;&nbsp;</option>';
					}
				}
				$elementString .= "\n</select>";
				break; 
				
				
			case 'radio':
				$elementString .= "\n";
				if (!empty($this->seleu_itemlist))
				{
					// Process each form item
					foreach ($this->seleu_itemlist AS $value => $label)
					{						
						$htmlselected = "";
						if ($value == $this->value) {
							$htmlselected = ' checked="checked"';						
						}
						
						$elementString .= "\n\t\t\t";
						
						// Create a class that contains this answer and item name, so that we can do specific work with CSS/jQuery if needed.
						$elementString .= '<div class="radio_item '.sanitize_title($this->name . '-' .$value).'" $attributeString><input type="radio" name="'.$this->name.'" '.$elementClass.' value="'.$value.'"'.$htmlselected.' style="width: auto;">&nbsp;&nbsp;'.$label;
						
						// Have we got an item that needs to be shown after this item? If so, grab 
						// the HTML for it and append it.
						if ($suffixHTML = $this->gotSuffixItemHere($value))
						{
							$elementString .= $suffixHTML;
						}
						
						// Closing wrapper div.
						$elementString .= "\n\t\t\t</div>";
					}
				}
				$elementString .= "\n";
				break; 
			
			case 'textarea':				
				$elementString .= "<textarea name=\"$this->name\" rows=\"$this->textarea_rows\" cols=\"$this->textarea_cols\" $elementID $elementClass $attributeString>$this->value</textarea>";  
				break; 

            case 'wysiwyg':
                $settings = array(
                    'teeny' => true,
                    'textarea_rows' => 15,
                    'tabindex' => 1
                );
                ob_start();
                wp_editor($this->value, $this->name, $settings);
                $elementString .= ob_get_clean();
                break;

			case 'uploadfile':
				if ($this->showExistingFile) {
					$elementString.= '<div class="existing_file">'. apply_filters('formbuilder_existing_file', $this->value) . '</div>';
				}
				$elementString .= "<input type=\"file\" name=\"$this->name\" $elementClass $elementID $attributeString/>";  
				break; 				
				
			case 'checkbox':
				$checked = "";
				if ($this->value == 1 || $this->value == "on")
					$checked = ' checked=checked';
				
				$elementString .= "<input type=\"checkbox\" name=\"$this->name\" $checked $elementClass $elementID $attributeString/> $this->checkbox_label";  
				break;
				
			case 'checkboxlist':
				if ($this->seleu_itemlist) 
				{					
					$totalCols = $this->checkboxListCols;
					
					// If we only have a few items, reduce the number of columns.
					if (count($this->seleu_itemlist) < (2*$this->checkboxListCols)) {
						$totalCols = floor($totalCols/2);
					}
					
					$itemCount = 0; // Current item we're dealing with per col
					$itemsPerCol = ceil(count($this->seleu_itemlist) / $totalCols); // The number of items per column					
					$closedOff = false; // Flag indicating if we've closed off the div section.
										
					// Width style to indicate how wide to set the column.
					$colWidth = floor(100/$totalCols);
					
					foreach ($this->seleu_itemlist AS $value => $label)
					{
						// Start column off
						if ($itemCount == 0) {
							$elementString .= sprintf('<div class="form-checklist-col" style="float: left; width: %s%%;" $attributeString>', $colWidth);
							$closedOff = false;
						}
						
						$htmlselected = "";
						if (is_array($this->value) && array_key_exists($value, $this->value)) {
							$htmlselected = ' checked="checked"';
						}
						
						$elementString .= "\n\t\t\t";
						$elementString .= sprintf('<input type="checkbox" name="%s_%s" class="%s_%s" %s style="width: auto"/>&nbsp;%s<br/>', 
											$this->name, $value,
											$this->name, $value,
											$htmlselected, 
											$label
										);
						$itemCount++;
										
						// Finish column off
						if ($itemCount >= $itemsPerCol) {
							$itemCount = 0;
							$elementString .= '</div>';
							$closedOff = true;
						}
						
					} // end foreach
					
					// Add closing divs
					if (!$closedOff) {
						$elementString .= '</div>';
					}
					
					$elementString .= "\n";		
				}			
				break; 
							
			/* A static type is just the value field. */
			case 'static':
				$elementString .= $this->value;
				break;
				
			/* Custom elements - just dump the provided HTML */
			case 'custom':
				$elementString .= $this->customHTML;
				break;
				
			/* Merged elements - turn each sub element into HTML, and render. */
			case 'merged':
					
					$elementString .= '<table><tr>';
					
					// Wrap inner elements in a table.
					if (!empty($this->subElementList))
					{
						foreach ($this->subElementList as $subelem)
						{
							$elementString .= '<td>';
							$elementString .= $subelem->toString(true, true);
							$elementString .= '</td>';
						}
					}
					
					$elementString .= '</tr></table>';
				break;

            /* Number input element */
            case 'number':

                // Add a default style
                if (!$this->cssclass) {
                    $elementClass = 'class="regular-text"';
                }

                // Got a min length?
                $elementMin = '';
                if ($this->number_min !== null) {
                    $elementMin = " min=\"$this->number_min\"";
                }

                // Got a max length?
                $elementMax = '';
                if ($this->number_max !== null) {
                    $elementMax = " max=\"$this->number_max\"";
                }

                // Set the element step, this should always be present
                $elementStep = " step=\"$this->number_step\"";;

                $elementString .= "<input type=\"number\" name=\"$this->name\" value=\"$this->value\" $elementMin $elementMax $elementStep $elementID $elementClass $attributeString/>";
                break;

            /* It's a date/datetime field */
            case 'datetime-local':
            case 'date':

                $format = WPUC_DATE_FORMAT_HTML5_DATETIME_LOCAL;
                if ($this->type == 'date') {
                    $format = WPUC_DATE_FORMAT_HTML5_DATE;
                }

                // Add a default style
                if (!$this->cssclass) {
                    $elementClass = 'class="regular-text"';
                }

                // Got a min date?
                $elementMin = '';
                if ($this->date_min !== null) {
                    $elementMin = ' min="' . $this->date_min->format($format) . '"';
                }

                // Got a max date?
                $elementMax = '';
                if ($this->date_max !== null) {
                    $elementMax = ' max="' . $this->date_max->format($format) . '"';
                }

                $date_value = new DateTime($this->value);

                $elementString .= '<input type="' . $this->type . '" name="' . $this->name . '" value="' . $date_value->format($format) . '"' . " $elementMin $elementMax $elementID $elementClass $attributeString/>";
                break;


            /* The default is just a normal text box. */
			default:
				// Add a default style
				if (!$this->cssclass) {
					$elementClass = 'class="regular-text"';
				}
				
				// Got a max length?
				$elementSize = false;
				if ($this->text_maxlen > 0) {
					$elementSize = " maxlength=\"$this->text_maxlen\"";
				}
					
				$elementString .= "<input type=\"text\" name=\"$this->name\" value=\"$this->value\" $elementSize $elementID $elementClass $attributeString/>";
				break; 
		}
		
		$elementString .= "\n";
				
		// Add extra HTML after form element if specified
		if ($this->afterFormElementHTML) {
			$elementString .= $this->afterFormElementHTML . "\n";
		}
		
		// Only add description if one exists.
		if ($this->description) {
			$elementString .= "\t\t".'<span class="setting-description"><br>'.$this->description.'</span>'."\n";
		}
		
		// Close off row tags, except if a merged field
		if (!$showAsMergedField) 
		{
			$elementString .= "\t</td>\n"; 				
			$elementString .= '</tr>'."\n";
		}

		return $elementString;
	}
	
	
	
	/**
	 * Determines if the value for this field is valid.
	 * @return Boolean True if the value is valid, false otherwise.
	 */
	function isValid()
	{
		// Non-user entries are always valid
		// TO DO Validate individual merged items. 
		if ($this->type == 'static' || $this->type == 'hidden' || $this->type == 'merged')
			return true;
			
			
		// For multi-item lists, should be at least one value
		if ($this->type == 'checkboxlist') 
		{
			if ($this->required) {
				$listValid = is_array($this->value) && count($this->value) > 0;
				if (!$listValid) {
					$this->renderWithErrors = true;
					return false;
				}
			}

			// Continue validaitng lists if not required, or required, but valid so far.
		}
		// Not multi-item lists
		else 
		{		
			// For file uploading, check $_FILES instead
			if ($this->type == 'uploadfile') 
			{				
				// Always true if not required
				if (!$this->required) {
					return true;
				}
				$fileValid = isset($_FILES[$this->name]) && (isset($_FILES[$this->name]['tmp_name'])) && ($_FILES[$this->name]['tmp_name'] != false);
				
				// If we want the field to be valid if the form has a value or a file is uploaded,
				// then the field is valid if there's any kind of value.
				if ($this->validIfValue && $this->value) {
					return true;	
				}
				
				if (!$fileValid) {
					$this->renderWithErrors = true;
				}
				return $fileValid;
			}
				
			// Field is required, but empty
			if ($this->required && $this->value == false) {
				$this->renderWithErrors = true;
				return false; 
			}
				
			// Field is not required, and empty
			if (!$this->required && $this->value == false) 
				return true;				
		}
		
		// Validation functions override internal validation
		if ($this->validationFn) 		
		{
			// Abort if function doesn't exist.
			if (!function_exists($this->validationFn)) {
				error_log('FormBuilder: Function "'.$this->validationFn.'" doesn\'t exist');
				return false;
			}
			
			// Allow the function to change the error message if required
			// function HL_validate_CountyField($county, $fieldName = false, $customError = false) {}
			// Setting $customError = 'String' makes the 'String' the error message.
			$customError = false;
						
			$validVal = call_user_func($this->validationFn, $this->value, $this->name, $customError);
			$this->renderWithErrors = !$validVal;

			// Have we been passed a different error message?
			if ($customError) {
				$this->errorMessage = $customError;
			}
			
			
			return $validVal;
		}
		
		// No validation function, see if we have any internal validation
		// rules. If so, validate the value against those.
		if ($this->validationRules)
		{
			$customError = false;
			
			$validVal = $this->validateValueUsingRules($this->value);
			$this->renderWithErrors = !$validVal; 
						
			return $validVal;
		}
					
		// No validation rules or functions, so it's all fine.
		return true;
	}
	
	
	/**
	 * Set the validation rules using an array of details.
	 * @param Array $rules The list of rules to enforce.
	 */
	public function setValidationRules($rules)
	{
		// Just copy rules, we'll look at them when we validate the element.
		if (is_array($rules)) {
			$this->validationRules = $rules;
		}
		
		// Set to false if invalid
		else {
			$this->validationRules = false;
		}
	}
	
	
	/**
	 * Validate a value according to the validation rules.
	 * @param String $fieldValue The value to validate.
	 */
	protected function validateValueUsingRules($fieldValue)
	{
		$validationType     = self::getArrayValue($this->validationRules, 'type');
		$this->errorMessage = self::getArrayValue($this->validationRules, 'error');
		$isValid = true;
		
		// Handle maximum and minimum length fields.
		switch($validationType)
		{
			// Standard strings, where length is important.
			case 'string':
			case 'email':	
			case 'url':
				
				// 1st stage, are there enough characters?
				$minlen = 0;
				if (isset($this->validationRules['minlen'])) 
				{
					$minlen = $this->validationRules['minlen'] + 0;
					if ($minlen > 0) {
						$isValid = strlen($fieldValue) >= $minlen;
					}
				}
				
				// 2nd stage, are there too many characters?
				$maxlen = 0;
				if (isset($this->validationRules['maxlen']))
				{
					$maxlen = $this->validationRules['maxlen'] + 0;
					if ($maxlen > 0 && $isValid) {
						$isValid = strlen($fieldValue) <= $maxlen;
					}		
				}
	
				// Length validation failed.
				if (!$isValid) {
					return false;
				}
			break;
			
			// Don't bother doing length for these fields, as they have a different
			// measure of what's valid based on their structure.
			case 'telephone':			
			case 'postcode':
			case 'number':
			case 'decimal':	
			case 'count':
            case 'date':
				break;
				
			// Unknown validation type.
			default:
				error_log('validateValueUsingRules(): Unknown validation type.');
				return false;
			break;				
				
		}
		
		// More complex validation happens now.
		switch($validationType)
		{			
			// ### Lists - counting items
			case 'count':
				if (isset($this->validationRules['max']))
				{
					$maxcount = $this->validationRules['max'] + 0;
					
					// Unlimited
					if ($maxcount == -1) {
						$isValid = true;
					} 
					
					// 0 items
					else if ($maxcount == 0) { 
						HL_debug_showArray($this->value);
						$isValid = (empty($this->value) || count($this->value) == 0);
					}
					
					// 1 or more items
					else {
						if (!empty($this->value) && is_array($this->value)) {
							$isValid = count($this->value) <= $maxcount; 
						}
					}
				}
				break;
			
			
			
			// ### Generic number
			/*
			 	'validate'	=> array(
					'type'	=> 'number',
					'max'	=> 50,
					'min'	=> 1,
					'error'	=> 'Please choose a number between 1 and 50.'
				) 
			 */			
			case 'number':
				// 1st stage, is it a number
				$isValid = is_numeric($fieldValue);
				
				// 2nd stage, do we have any ranges?
				if ($isValid)  
				{
					$fieldValue += 0; 
					
					// Do we have a minimum value?
					if (isset($this->validationRules['min'])) {
						$isValid = $fieldValue >= $this->validationRules['min'];
					}

					// Do we have a maximum value?
					if ($isValid && isset($this->validationRules['max'])) {
						$isValid = $fieldValue <= $this->validationRules['max'];
					}					
				}
			break;
			
			// ### Special Numbers
			
			// Decimal
			/*
			 	'validate'	=> array(
					'type'	=> 'decimal',
					'max'	=> 9999.99,
					'min'	=> 0.01,
					'error'	=> 'Please choose a starting price between 0.01 and 9999.99.'
				) 
			 */
			case 'decimal':
				// 1st stage, is it a decimal number?
				$isValid = preg_match('/^[0-9]+(\.[0-9]{1,2})?$/', $fieldValue);
				
				// 2nd stage, do we have any ranges?
				if ($isValid)  
				{
					$fieldValue += 0; 
					
					// Do we have a minimum value?
					if (isset($this->validationRules['min'])) {
						$isValid = $fieldValue >= $this->validationRules['min'];
					}

					// Do we have a maximum value?
					if ($isValid && isset($this->validationRules['max'])) {
						$isValid = $fieldValue <= $this->validationRules['max'];
					}					
				}
			break;			
			
			// ### Generic string
			/*
			   'validate'	 	=> array(
					'type'		=> 'string',
					'maxlen'	=> 100,   						// (optional) The maximum length of the string
					'minlen'	=> 1,							// (optional) The minimum length of the string
					'regexp'	=> '/^[A-Za-z0-9\'\-\ ]+$/',	// (optional) A normal regular-expression of what's permitted in the string.
					'error'		=> 'Explain what's valid.'		// (optional) The error message if the string doesn't validate.
				)	
			 */
			case 'string':
				// Validate against a regular expression
				$regexp = FormElement::getArrayValue($this->validationRules, 'regexp'); 				
				if ($regexp) {
					$isValid = preg_match($regexp, $fieldValue, $matches);
				}				
			break;

            // Valid dates (type = datetime-local or type = date)
            case 'date':
                $parsed_date = false;

                // Validate against a date format
                if (isset($this->validationRules['format'])) {
                    $isValid = $parsed_date = DateTime::createFromFormat($this->validationRules['format'], $fieldValue);
                }

                // Validate date is in the future
                if ($isValid && $this->validationRules['future'] === true) {
                    $now = new DateTime();
                    $isValid = $parsed_date > $now;
                }

                // Validate date is in the past
                if ($isValid && $this->validationRules['past'] === true) {
                    $now = new DateTime();
                    $isValid = $parsed_date < $now;
                }

                break;

			// ### Special strings
			
			// Valid Telephone Number  (type = telephone)
			case 'telephone':
					// Examples of valid numbers are: 
					// 01234 123345
					// +44 12345 123455
					$nospaces = str_replace(' ', '', $fieldValue);
					$isValid = preg_match('/^\+?([0-9]){9,14}$/', $nospaces);	
				break;
								
			// Valid URLs (type = url)		
			case 'url':
					//$isValid = preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $fieldValue);	
					$isValid = preg_match("/(https?|ftp):\/\/(www\.)?[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $fieldValue);
				break;		
				
			// Valid Email Addresses (type = email)	
			case 'email':
					$isValid = preg_match('/^[A-Za-z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/', $fieldValue);	
				break;		

			// Valid UK Postcode (type = postcode)	
			/**
 				Validates against these:
 				
				A9 9AA 		M1 1AA 		B, E, G, L, M, N, S, W postcode areas
				A99 9AA 	M60 1NW
				AA9 9AA 	CR2 6XH 	All postcode areas except B, E, G, L, M, N, S, W, WC
				AA99 9AA 	DN55 1PT
				A9A 9AA 	W1A 1HQ 	E1W, N1C, N1P, W1 postcode districts (high density areas where codes ran out)
				AA9A 9AA 	EC1A 1BB 	WC postcode area; EC1�EC4, NW1W, SE1P, SW1 postcode districts (high density areas where codes ran out
			 */			
			case 'postcode':
					$isValid = preg_match('/^([A-Z]([0-9]{1,2}|[A-Z][0-9]{1,2}|[A-Z]?[0-9][A-Z]))\ ([0-9][A-Z]{2})$/i', $fieldValue);	
				break;		
		}
		
		return $isValid;
	}
	
	
	/**
	 * Get the error message if there's something wrong with this form field.
	 * @return String The error message if there's something wrong with this field.
	 */
	function getErrorMessage()
	{
		// Ah, we have a custom message, use that.
		if ($this->errorMessage) {
			return $this->errorMessage;
		}
		
		// Field is required, but empty, so return a fill in this form message.
		else if ($this->required && $this->value == false) 
			return sprintf($this->getTranslationString("Please fill in the required '%s' field."), $this->label);
	
		// Have we got an empty error message? Create a default one
		else if (!$this->errorMessage) {
			return sprintf($this->getTranslationString("There's a problem with value for '%s'."), $this->label);
		} 
	}
	
	
	/**
	 * Gets the translated string from the parent object, checking there is a parent
	 * object first. If not, then the default string is returned.
	 * 
	 * @param String $str The object to translate.
	 * @return String The translated string.
	 */
	protected function getTranslationString($str)
	{
		// Only return string if there is a parent object and a translation
		if ($this->parent && $trStr = $this->parent->getTranslationString($str)) {
			return $trStr;
		}
		return $str;
	}
	
	
	/**
	 * Updates the list of suffix items with fields to include as a compound field
	 * for this form. The array contains a list of 'position' => array(fieldName => fieldDetails)
	 * 
	 * @param Array $listOfFieldDetailsAndPositions 
	 */
	public function setSuffixItems($listOfFieldsAndPositions)
	{
		$this->suffixItems = $listOfFieldsAndPositions;
	}
	
	
	/**
	 * Find out if we need to render any elements at this position in the form.
	 * @param String $positionName The name of the position that matches the name of the field if used with radio items, or 'end' for right at the end.
	 */
	function gotSuffixItemHere($positionName)
	{
		// No items
		if (empty($this->suffixItems)) 
			return; 
		
		// See if we have an item at this position
		if (isset($this->suffixItems[$positionName]))
		{
			// Ensure we wrap the sub items up.
			$html = '<div class="subelement_wrapper_all">';
			
			// There may be multiple items at this position.				
			foreach ($this->suffixItems[$positionName] as $item)
			{
				// Also wrap a single item for more styling capability.
				$html .= sprintf('<div class="subelement_wrapper_item %s">', (!$item->isValid() ? 'subelement_wrapper_item_has_errors' : ''));
					$html .= $item->toString(true, true); // Don't show the table wrappers
				$html .= '</div>';
			}
			$html .= '</div>';
			
			return $html;
		}
				
		return false;
	}	 
	
	
	/**
	 * Simple safe function to get an array value first checking it exists.
	 * @param Array array The array to retrieve a value for.
	 * @param String $key The key in the array to check for.
	 * @return String The array value for the specified key if it exists, or false otherwise.
	 */
	public static function getArrayValue($array, $key)
	{
		if (isset($array[$key])) {
			return trim(stripslashes($array[$key]));
		}
		return false;
	}

	
	/**
	 * Function that extracts the data from the array into the form field, based on the type of field it is.
	 * @param Array $rawData The raw data to extract information from. 
	 */
	public function extractValuesFromArray($rawData)
	{
		// Elements with lots of selected values, so copy list of values across.			
		if ($this->type == 'checkboxlist') 
		{	
			// Dynamic function to retrieve all fields that start with the element name
			// for multi-item lists.			
			$filterFunc = create_function('$v', '$filterStr = "'.$this->name.'_"; return (substr($v, 0, strlen($filterStr)) == $filterStr);');
			
			// Extract all values for this multi-item list.
			$itemList = array_filter(array_keys($rawData), $filterFunc);
			
			// Might have no items
			$this->value = array();
			
			// If we've got some values, extract just the values of the list				
			if (count($itemList) > 0) 
			{				
				$regexp = sprintf('/%s_(.*)/', $this->name);
				
				foreach ($itemList as $fieldname)
				{
					// Extract the actual field name from the list, and then assign it 
					// to the internal list of values for this particular multi-item field.
					if (preg_match($regexp, $fieldname, $matches)) {
						
						// Proper value is still held in $rawData, so retrieve it using
						// full name of field (field name plus sub-item name)
						$this->value[$matches[1]] = $this->getArrayValue($rawData, $fieldname);  
					}
				}
			}	
		}
		
		
		// Merged elements, so extract each of their values
		else if ($this->type == 'merged') 
		{
			$mergedValueList = array();
			if (!empty($this->subElementList))
			{
				foreach ($this->subElementList as $subElem)
				{
					// Extract the value from the $rawData array, and add to array for this element
					$mergedValueList[$subElem->name] = $this->getArrayValue($rawData, $subElem->name);
					 
				}
				$element->setValue($mergedValueList);
			}							
		}
		
		// Single value element - just copy standard post value
		else {
			$this->value = $this->getArrayValue($rawData, $this->name);
		}
		
		// Handle elements with a suffix list. 
		if (!empty($this->suffixItems))
		{
			// List of positions => elements
			foreach ($this->suffixItems as $position => $elementList)
			{
				// Each object in this position...
				foreach ($elementList as $elementItem)
				{
					// Just use this function to extract data.
					$elementItem->extractValuesFromArray($rawData);
				} 
			} // end foreach 
		} // end if
	}
	
	
	/**
	 * Get the list of values in the suffix list, if there are any.
	 * @return Array The values for the elements in the suffix list.
	 */
	public function getSuffixValues()
	{		
		if (!empty($this->suffixItems))
		{
			$listToReturn = array();
			
			// List of positions => elements
			foreach ($this->suffixItems as $position => $elementList)
			{
				// Each object in this position...
				foreach ($elementList as $elementItem)
				{
					$listToReturn[$elementItem->name] = $elementItem->value;
				} 
			} // end foreach

			return $listToReturn;
		} // end if	
		
		return false;
	}
	
}}

?>