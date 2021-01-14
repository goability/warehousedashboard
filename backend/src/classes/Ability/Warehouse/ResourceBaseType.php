<?php
namespace Ability\Warehouse;

class ResourceBaseType
{

  //Instance Properties
  public   $DB_Fields; //database fields and VALUES, keyed by DB Columnname
  public   $DB_Labels             = []; //labels that go on the form, keyed by DB Columnname
  public   $DependentResources    = []; //Linked external labels (Like StorageItems at a location)
  public   $DependentResourceData = []; //filled with records
  public   $ReportConfig          = [];//Holds column and reporting details
  public   $Associations          = [];//Linked collections using an associative table
  public   $AssociationData       = [];//filled with records
  public   $ImageFilename;


  // fields that should not be overridden
  public    $ID = 0; //Assume a new record=0, unless loadrecord finds a matching one
  public    $Name;
  protected $timestamp;//last accessed
  public    $FormProperties;//[action, showCancel, mode, ...
  public    $FormMode = "";
  private   $Cached_Records = [];//TODO Array of Cached (Types)
  public    $tableName;
  public    $accessToken;

  // STATIC Properties

  public static $IndexFieldName     = null;
  public static $OrderByFieldName  = null;
  public static $OrderByDirection  = null;
  public static $OrderyByDirection  = "DESC";
  public static $ResourceName       = null; // temp
  public static $TableName          = null;
  public static $ClassName          = null;

  public static function CreateFromRecord($record)
  {
    //Set some static properties applicable for all of these objects
    $resourceName = static::$ResourceName;
    $resourceToLoad = NAME_SPACE . "\\" . $resourceName;

    $instance = new $resourceToLoad();
    $indexFieldName = $instance::$IndexFieldName;
    //A record was passed in, create the object using this data
    $instance->DB_Fields  = $instance->_populateDBFields($record);
    $instance->ID         = $instance->DB_Fields[$indexFieldName];
    $instance->additionalRecordSetup();

    return $instance;
  }
  /*
  * Logic that should only occur once for this resource in a flow:
  *
  *    -- Load the configuration and class descriptors
  *    -- Determine which resources this resource owns, load ownershipInfo
  */
  public static function StaticConstructor($resourceName){

    if (is_null(static::$ResourceName)){

      Log::debug("-------- STATIC CONSTRUCTOR FIRST TIME FOR  [$resourceName] Setting up properties common for all [$resourceName](s) ");

      static::$TableName            = strtolower(ConfigurationManager::GetResourceConfigParameter($resourceName, 'tableName'));
      static::$ResourceName         = $resourceName;
      static::$IndexFieldName       = ConfigurationManager::GetResourceConfigParameter($resourceName, 'indexFieldName');


      static::$DisplayName          = ConfigurationManager::GetResourceConfigParameter($resourceName, 'displayName');
      static::$FormTitle            = ConfigurationManager::GetResourceConfigParameter($resourceName, 'formTitle');
      static::$ClassName            = get_called_class();// TODO: get rid of this
      static::$OwnedByResourceName  = ConfigurationManager::GetResourceConfigParameter($resourceName, 'ownedByResourceName');
      static::$OwnedByFieldName     = ConfigurationManager::GetResourceConfigParameter($resourceName, 'ownedByFieldName');

      static::$OrderByFieldName     = ConfigurationManager::GetResourceConfigParameter($resourceName, 'orderByFieldName');
      static::$OrderyByDirection    = ConfigurationManager::GetResourceConfigParameter($resourceName, 'orderByDirection');

      Log::debug("All setup tableName is [" . static::$TableName . "]");
    }
  }

  function additionalRecordSetup(){}//abstract
  /*
  Create a new Generic Object
  @param $dataJSONObj - JSON object describing the resource
  @param $recordID - Load a specific record OR null for new record
  @param $record - pass in an associative array and populate the object
  */
  function __construct($recordID=null)
  {
    $resourceName = static::$ResourceName;

    $resourceConfigObject = ConfigurationManager::GetResourceConfig($resourceName);

    if (empty($resourceConfigObject) || !isset($resourceConfigObject["tableName"])){
          Log::error("ERROR FATAL RESOURCE CONSTRUCT CONFIG ERROR ========= EMPTY resourceJSONObject for $resourceName ");
          die();
    }

    //// TODO: Get this and all session references out of all resource objects
    $this->accessToken = SessionManager::GetParameter(Constants\SessionVariableNames::ACCESS_TOKEN);

    //Populate DB_Fields and DB_Labels using properties from configuration
    // Keys for both will match DB column names

    // Fields holds values, so just fill it empty "" for now
    $this->DB_Fields = array_fill_keys(array_keys($resourceConfigObject["fields"]), "");

    // Labels are populated with data from config describing the data-type and form labels to show
    $this->DB_Labels = array_replace($this->DB_Labels, $resourceConfigObject["fields"]);

    if (array_key_exists("dependentCollections", $resourceConfigObject))
      $this->DependentResources = array_replace($this->DependentResources, $resourceConfigObject["dependentCollections"]);

    if (array_key_exists("associativeCollections", $resourceConfigObject)){
        $this->Associations = array_replace($this->Associations, $resourceConfigObject["associativeCollections"]);
    }
    if (array_key_exists("reporting", $resourceConfigObject)){
        //Reports contain what to show for a report for this resource
        $this->ReportConfig = array_replace($this->ReportConfig, $resourceConfigObject["reporting"]);
    }
    $this->_formPath = "forms/formGeneric.php";

    $recordID = intval($recordID);
    //If a recordID comes in as null, it implies a new record
    if ($recordID>0)
    {

      if ($this->loadRecord($recordID)){

        $this->ID = $recordID;
        $this->ImageFilename = isset($this->DB_Fields["imagename_main"]) ?
              $this->DB_Fields["imagename_main"] : null;

        if(!empty($this->Associations)){
          $this->AssociationData = DataProvider::GetAssociatedRecords($this);
        }
        if(!empty($this->DependentResources))
        {
          $this->DependentResourceData = DataProvider::GetDependentRecords($this);
        }
      }
    }
  }
  //Return JSON Representation
  public function toJSON()
  {
    //Simply load the record into this object
    return json_encode($this->DB_Fields);
  }
  public function _populateDBFields($record){
    return array_replace($this->DB_Fields, $record);
  }
  //Get One record by name
  public function GetField($name)
  {
    return $this->DB_Fields[$name];
  }
  public function SetField($name, $value)
  {
    if (isset($this->DB_Fields[$name])){
      $this->DB_Fields[$name] = $value;
    }
  }
  public function PrepareFormData($formData){

    $preparedFormData = $formData;

    foreach ($formData as $key => $value) {
      if (isset($this->DB_Labels[$key])){
        if ($this->DB_Labels[$key]['dataType']==='int'){
          $preparedFormData[$key] = intval($value);
        }
      }
    }

    return $preparedFormData;
  }

  //////////////////////////////////
  // DATABASE Functions
  /*
  Load one or more records.
  If one record, values are copied into $this->DB_Fields
  For multiple records, only the index and displays will be stored in _UserCollection Object

  */
  protected function loadRecord($recordID)
  {

    if ( !isset($recordID) || $recordID==0 ){
        Log::error("Error loading records for resource ID [$recordID]");
        return false;
    }

    try {

          $row = DataProvider::LOAD(Constants\SqlPrepareTypes::SQL_SELECT_ONE . static::$TableName, $this->DB_Labels, $recordID);
          if (is_null($row) || count($row)==0)
          {
            Log::error("No record for ID $recordID");
            $this->ID = 0;
            return false;
          }

          $this->DB_Fields  = array_merge($this->DB_Fields, array_intersect_key($row, $this->DB_Fields));
          $indexFieldName   = get_called_class()::$IndexFieldName;
          $userID           = $this->ID = $row[$indexFieldName];

          // REMOVE FIELDS THAT DON'T NEED TO BE EDITED
          //  This affects all transactions because the form elements in config
          //   MUST match the items returned by the database, so these are removed
          // TODO: Do this cleaner with an array_intersect
          if (isset($this->DB_Fields['upasswd'])){
            unset($this->DB_Fields['upasswd']);
          }
          if (isset($this->DB_Fields['verified'])){
            unset($this->DB_Fields['verified']);
          }
          if (isset($this->DB_Fields['verified_timestamp'])){
            unset($this->DB_Fields['verified_timestamp']);
          }
        }
    catch (Exception $e){
      Log::error($e->getMessage());
      return false;
    }
    return true;
  }
  /*
    Sets provided dictionary into DB
    $fieldData[$fieldName]=value WHERE $fieldName is same as DB FieldName
    @returns new record that was inserted
  */
  public function InsertRecord($fieldData)
  {
    $this->DB_Fields = array_replace($this->DB_Fields, array_intersect_key($fieldData, $this->DB_Labels));
    return DataProvider::INSERT(get_called_class()::$TableName, array_values($this->DB_Fields));
  }
  public function UpdateRecord($fieldData){
    $this->DB_Fields = array_replace($this->DB_Fields, array_intersect_key($fieldData, $this->DB_Labels));
    $this->DB_Fields['id'] = $this->ID;
    return DataProvider::UPDATE(get_called_class()::$TableName, $this->DB_Fields);
  }
  /**
  * Delete one record where ID=$Id
  */
  public function DeleteRecord($id)
  {
    return DataProvider::DELETE(get_called_class()::$TableName, $id);
  }
  public function GET($id)
  {
    $this->loadRecord($id);
    return $this->Properties;
  }

  final protected function getClass()
  {
    return trim(get_class($this));
  }
  public function SetFormProperties($props)
  {
    $this->FormProperties = $props;
    $this->FormMode = $props["MODE"];
  }
  public function GetFormProperty($name)
  {
    $returnValue = null;
    if (key_exists($name, $this->FormProperties))
    {
      $returnValue = $this->FormProperties[$name];
    }
    return $returnValue;
  }
  ////////////////////////////////////////////
  // Public Functions
  /*
    Show the Record Form in HTML
    @param ID - recordID
    @includes the form that the resource has indicated in its ctor
  */
  public function ShowForm()
  {
    global $formProperties; //array
    echo "<div class='content-container'>";
    require_once($this->_formPath);
    echo "</div>";
  }


  /*
  Build an html div with list of records
  @param: resources - array of resource objects (Users, StorageItems, etc)
  @param: componentID - ID and name to set as the component on the HTML form
  @param: size number of items, defaults to 4
  @param: navbar - top navigation bar for the set.  Add, ..
  @param: navbarItem - item level navigation bar.  Delete, ..
  @param: $callbackFuncName - name of a function to call to add a menu for each record
  @param: $callbackFuncData - data to pass into that function (ORDERED)

  */
  function showAssociationsAsList($elementID, $resources, $size=4, $navbar=null, $callbackFuncName=null, $callbackFuncData=null, $extraRowData=null, $callingResource=null){

    $retHTML = "<div class='container-fluid'>";
    $resourceName = static::$ResourceName;

    if (!empty($navbar))
    {
      $retHTML .= "<div class='container-fluid'>";
      $retHTML .= $navbar;

      $retHTML .= "</div>";
    }
    $retHTML .= "<div class='container-fluid' style='margin:0; padding:0' id='$elementID" . "DIV'>";
    $retHTML .= "<ul class='list-group' style='margin:0; padding:0;' id='$elementID" . "LIST'>";

    foreach ($resources as $resource) {

      $retHTML .= "<li id='$elementID-item-$resource->ID' class='list-group-item' aria-hidden='true' style='margin:0; padding:1;'>";
      //Attach the click handler to this line item, and add this resourceID
      $retHTML .=  !empty($callbackFuncName) ?
                      call_user_func_array(array(__NAMESPACE__ . "\UIManager",
                                            $callbackFuncName),
                                            array_merge($callbackFuncData, array($resource->ID))
                                            ) : "";

      $retHTML .= $resource->GetListItemText($callingResource);
      $retHTML .= !empty($extraRowData) ? $extraRowData[$resource->ID] : "";
      $retHTML .= "</li>";
    }

    $retHTML .= "</ul></div>";

    return $retHTML;
  }
  public function GetListItemText($callingResource=null)
  {
    return isset($this->DB_Fields["name"]) ?
              $this->DB_Fields["name"] : null;
  }
  public function GetDisplayText()
  {
    return isset($this->DB_Fields["name"]) ?
              $this->DB_Fields["name"] : null;
  }
  public static function GetDisplayFieldsCSV($resourceName){

    $displayFieldStr = 'name';
    $fqrn = NAME_SPACE."\\".$resourceName;
    $tableName = $fqrn::$TableName;

    $displayFieldNames = ConfigurationManager::GetResourceConfigParameter($fqrn::$ResourceName, 'displayFieldName');

    if (count($displayFieldNames)>1){
      $displayFieldStr = "CONCAT("; //"$tableName.firstname, ' ', users.lastname)';
      foreach ($displayFieldNames as $fieldName) {
        $displayFieldStr .= "$tableName.$fieldName" . ", ' ',";
      }
      $displayFieldStr = substr($displayFieldStr, 0, -6);//take off the last , ' ',
      $displayFieldStr .= ") as name";
    }
    else{
      $displayFieldStr = $displayFieldNames[0];//usually just name
    }

    return $displayFieldStr;
  }

  /* Build a drop-down selection of selectable records
    @returns string HTML select component
  */
  public function ShowSelectRecordNavigation()
  {
    $accessibleRecordIDs = SessionManager::GetOwnedRecordIDs(static::$ResourceName);
    return $this->buildSelectCombo(true, $accessibleRecordIDs, $this->ID);
  }
  /* Build a search box for finding records
    @returns string HTML select component
  */
  public function ShowSearchRecordNavigation($enableAutocomplete=false)
  {
    $html = "";
    if($enableAutocomplete){
      $html = "<b>search</b> <input type='text' id='resourceSearchString' name='resourceSearchString' size=20 maxlength=40>";
    }
    else{
      $html = "<b>search</b> <input type='text' id='resourceSearchString' name='resourceSearchString' size=20 maxlength=40>";
    }
    return $html;
  }
  /*
    @param $isFormNavigation - If true, indicates this component should change
    the form when values are selected, used as a record navigator
    @param $selectedID=null - ID to set as selected in the component
    @param $componentID=ID of this component on the html form
    @param $size - default number of rows
    @param $multiple - allow multiple selection
    @param $rowdata = optional array of data to show instead of looking up in database

  */
  public function buildSelectCombo( $isFormNavigation=false,
                                    $rowdata=null,
                                    $selectedID=null,
                                    $componentID="ID",
                                    $size=null,
                                    $multiple=false,
                                    $indexFieldName="id",
                                    $formID="formSelect")
  {

    $ret = "";
    $resourceName = static::$ResourceName;

    if (empty($rowdata)){
      return "Create your first $resourceName";
    }
    if (is_null($indexFieldName) || empty($indexFieldName)){
      $indexFieldName="id";
    }
    $resourceName = NAME_SPACE . "\\" . $resourceName;
    $tableName = $resourceName::$TableName;

    if (Util::array_key_first($rowdata)==='*'){
        $rowdata = DataProvider::GetAllRecordsForNav($resourceName);
      }

    if ($isFormNavigation)
    {
      $ret .= "<span class='form-control-label'></span> ";
    }

    //Start the select component
    $ret .= "<select value=0 id=\"" . $componentID . "\" name=\"" . $componentID . "\"";
    if ($isFormNavigation)
      $ret .= " onChange=\"document.getElementById('$formID').submit();\"";
    else {  // If not navigation, is it used as a drop-down or a list ?
      if (null!==$size)
        $ret .= " size=" . $size;
      if ($multiple)
        $ret .= " multiple";
    }
    $ret .= ">";


    $resourceDisplayName = static::$DisplayName;
    $ret .= "<option value=-1>Select a $resourceDisplayName</option>";

    if ( !empty($rowdata))
    {
      foreach ($rowdata as $value)
      {
        $ret .= "<option value=" . $value[$indexFieldName];
        if ($selectedID && $value[$indexFieldName]==$selectedID)
          $ret .= " selected";
        $ret .= ">". $value['name'] . "</option>";
      }
      $ret .= "</select>";
    }
    return $ret;

  }
  public function show_navRecordBar(){

    $ret .= "<form style='display:inline;'  action=\"?accessToken=$this->accessToken\" method=\"POST\">";

    if ($this->FormMode==="UPDATE"){
      $ret .= "&nbsp;<input type=\"submit\" name=\"add\" value=\"add new\" id=\"record-add\">";
      $ret .= $this->GetRecordDeleteElement();
    }
    $ret .= "</form>";

    if ($this->FormMode==="UPDATE"){
      $ret .= $this->show_navExtraButtons();
    }
  }
  /* Show an HTML form, ready to post to $resourceName (User, StorageItem, ...)
  */
  public function ShowFormNavigationSelect()
  {
    // TODO: these UI items are way overdue for being moved out,
    //      they do not need to be directly on the object, create a manager

    $ret = "<div style='display:inline;' class='resourceNav' id='resourceNav'><form style='display:inline;' class='none' id=\"formSelect\" action=\"?accessToken=$this->accessToken\" method=\"POST\">";
    $ret .= $this->ShowSelectRecordNavigation();

    if ($this->FormMode==="UPDATE"){
      $ret .= "&nbsp;<input type=\"submit\" name=\"add\" value=\"add new\" id=\"record-add\">";
      $ret .= $this->GetRecordDeleteElement();
    }
    $ret .= "</form>";

    if ($this->FormMode==="UPDATE"){
      $ret .= $this->show_navExtraButtons();
    }
    $ret .= "</div>";

    return $ret;
  }
  public function ShowFormRecordSearch(){


    $resourceName = static::$ResourceName;

    $ret = "<div style='display:inline;'>";
    $ret .= "<form style='display:inline;' class='none' id=\"formResourceSearch\" action=\"?accessToken=$this->accessToken\" method=\"POST\">";
    $ret .= $this->ShowSearchRecordNavigation(true);
    $ret .= "<input type='hidden' id='ID' name='ID' value=8800>";
    $ret .= "<input type='submit' id='action' name='action' value='edit'>";;
    $ret .= "</form>";

    $urlAPIAutoComplete = ConfigurationManager::GetParameter("APIURL") . "/Autocomplete";

    $ret .= "<script language=\"javascript\">";

    $ret .= "$( function() {

          $( \"#resourceSearchString\").autocomplete({
            source: '$urlAPIAutoComplete?api_key=993938jsd88h&resourceName=$resourceName',
            minLength: 1,
            select: function( event, ui ) {
              console.log(ui);
              var id = ui.item.id;

              $('#ID').val(id);

            $('#formResourceSearch').submit();

            }
          });
        } );
  ";

    $ret .="</script>";
    $ret .= "</div>";

    return $ret;
  }
  public function GetRecordDeleteElement(){
    return "&nbsp;<input onclick=\"return confirm('Confirm delete?')\" type=\"submit\" name=\"delete\" value=\"delete\" id=\"record-delete\">";
  }
  public function show_navExtraButtons($addReports=true){
    return "";
  }
  /*
     Show an HTML SELECT component that an be used as a field on a a form
     @param $selectedID - ID in the rendered list to set as $selected
     @param $componentName - Name of the component name of the HTML element
     @param $rowData - optional, ['id']['value to show']
  */
  public function ShowSelectDropDownComponent($selectedID, $rowData=null, $componentName=null, $size=null, $multiple=false, $indexFieldName='id')
  {
    return $this->buildSelectCombo(false, $rowData, $selectedID, $componentName, $size, $multiple, $indexFieldName);
  }
  /*
     Show an HTML Listbox component that an be used as a field on a a form
     @param $selectedID - ID in the rendered list to set as $selected
     @param $foreignFieldName - Name of the
  */
  public function ShowSelectListBoxComponent($selectedID, $foreignFieldName, $foreignKeyValue, $size=1,$multiple=false, $rowData=null)
  {
    return $this->buildSelectCombo(false, $rowData, $selectedID, $foreignFieldName, $size, $multiple);
  }
  /*
    GetSelectOptionItemText
     - Given an db results array, built a select optin line item
  *
  */
  public function GetSelectOptionItemText($record)
  {
    return "Undefined: " . $record['id'];
  }
  /*
    GetSelectListItemText
     - Given an db results array, built a list optin line item
  *
  */
  public function GetSelectListBoxItemText($record)
  {
    return GetSelectOptionItemText($record);
  }
  /*
    Show form components and data, one on each row
  */
  public function showFormRecordFields()
  {

    foreach ($this->DB_Labels as $fieldName=>$fieldDef)
    {
      //Only show rows if in UPDATE OR CREATE and not just a label
      //  the labels are formatted differently
      if ( !isset($fieldDef["read-only"]) &&
            ( $this->FormMode==='UPDATE' ||
              ($this->FormMode==='CREATE' && $fieldDef["formcomponent"]!="label")
            )
          ){

            //UI Form component size control
            $componentsize  = isset($fieldDef["componentsize"]) ? $fieldDef["componentsize"] : 20;
            $selectsize     = isset($fieldDef["componentsize"]) ? $fieldDef["componentsize"] : 1;
            $listsize       = isset($fieldDef["componentsize"]) ? $fieldDef["componentsize"] : 5;
            $maxlength      = isset($fieldDef["maxlength"]) ? $fieldDef["maxlength"] : 200;
            $rows           = isset($fieldDef["rows"]) ? $fieldDef["rows"] : 4;
            $cols           = isset($fieldDef["cols"]) ? $fieldDef["cols"] : 20;

        echo "<tr>";

        switch($fieldDef["formcomponent"])
        {
          case "textarea":
            echo "<td><label for='{$fieldName}'>{$fieldDef["formlabel"]}</label></td>";
            echo "<td align=left><textarea maxlength='$maxlength' rows='$rows' cols='$cols' id='{$fieldName}' name='{$fieldName}'>{$this->GetField($fieldName)}</textarea></td>";
            break;
          case "text":
            $fieldValueString = "value='{$this->GetField($fieldName)}'";

            echo "<td><label for='{$fieldName}'>{$fieldDef["formlabel"]}</label></td>";
            echo "<td align=left><input maxlength='$maxlength' size='$componentsize' type='{$fieldDef["formcomponent"]}' id='{$fieldName}' name='{$fieldName}' $fieldValueString></td>";
            break;
          case "select":

                $selectedExternalID = $this->GetField($fieldName);

                // This form has a component that is populated with data from another table.
                $linkedResourceName = explode(".",$fieldDef["linkedFieldKey"])[0];//User.Id would evalulate to a user object
                $linkedResourceClassName = NAME_SPACE . "\\" . $linkedResourceName;
                $linkedRecord = new $linkedResourceClassName();//Users[1], Storagefacility[100]
                $linkedRecordID = $linkedRecord->ID;

                echo "<td><label for='{$fieldName}'>{$fieldDef["formlabel"]}</label></td>";
                echo "<td align=left>";

                // TODO: This is hard-coded FOR NOW.   Providers that do not own a storage facility should
                //   still have ability to assign one to their record.  Current logic requires ownership

                if ($linkedResourceName=='Storagefacility'){
                  $rowData = DataProvider::GetAllRecordsForNav('Storagefacility');
                }
                else{
                  //Show a drop-down selectable list for this resource, using the current $fieldName as the HTML element id
                  $rowData = SessionManager::GetAccessibleRecordIDs($linkedResourceName);
                }

                $indexFieldName = (is_null($rowData) || empty($rowData)) ? null : Util::array_key_first($rowData[0]);

                echo $linkedRecord->ShowSelectDropDownComponent($selectedExternalID, $rowData, $fieldName,null, null, $indexFieldName);

                echo "</td>";

            break;
          case "list":
            echo "<td><label for='{$fieldName}'>{$fieldDef["formlabel"]}</label>a list </td>";
            echo "<td align=left>";
            // This form has a component that is populated with data from another table.  Call that objects function
            $linkedResourceName = explode(".",$fieldDef["linkedFieldKey"])[0];
            $linkedRecord = new $linkedResourceName();//i.e. Users[1], Storagefacility[100]
            echo $linkedRecord->ShowSelectListBoxComponent($this->GetField($fieldName), $fieldName, 10, true);
            echo "</td>";
            break;

          case "label":
            echo "<td align=left><input type='hidden' id='{$fieldName}' name='{$fieldName}' value='{$this->GetField($fieldName)}'>{$this->GetField($fieldName)}</td>";
            break;

          case "date":
            echo "<td><label for='{$fieldName}'>{$fieldDef["formlabel"]}</label></td>";
            echo "<td align=left><input type='date' id='{$fieldName}' name='{$fieldName}' value='{$this->GetField($fieldName)}' placeholder='dd-mm-yyyy' pattern='\d{1,2}-\d{1,2}-\d{4}'></td>";

            break;
          case "checkbox":
            $isChecked = false;
            //In configuration, a default value can be specified
            if ($this->FormMode==='CREATE'){
              if (isset($fieldDef['defaultvalue'])){
                $isChecked = $fieldDef['defaultvalue'];
              }
            }
            else{
              $isChecked = $this->GetField($fieldName);
            }
            echo "<td><label for='{$fieldName}'>{$fieldDef["formlabel"]}</label></td>";
            echo "<td align=left>";
            //https://stackoverflow.com/questions/1809494/post-unchecked-html-checkboxes/8972025

            echo "<input type='hidden' value=0 id='{$fieldName}' name='{$fieldName}'>";
            echo "<input type='checkbox' id='{$fieldName}' name='{$fieldName}' value=1 ";

            if ($isChecked){
              echo " checked";
            }

            echo ">";

            echo "</td>";
            break;
        }
        echo "</tr>";
      }
      else if ($this->FormMode==='CREATE' && $fieldDef["formcomponent"]==="label"){

        echo "<tr>";
        echo "<td align=left><input type='hidden' id='{$fieldName}' name='{$fieldName}' value=NULL></td>";
        echo "</tr>";
      }
    }
  }
  /*
    Show linked and associative table objects
    These are items that this object is related to in two different ways:
      1.) Linked Collection - These are things that another object has this one as an
      2.) Associative Collections - Things that are in an associative table (when there are 1:N type of relations)

    Having two collections allows for an ultimate owner, which might be a user or another object.
    At the same time, the association table allows multiple relations, which can be used in any way the application needs.

    // NOTE: this is different than 'ownership' of a resource, which is used
        in the security and authorization checks.  They can actually refer to the
        same field, but do not have to.
  */

  function showFormdependentCollections()
  {
    $currentUserID  = SessionManager::GetCurrentUserID();
    $isAdmin        = SessionManager::IsAdministrator();
    $isClient       = SessionManager::IsClient();

    $resourceName = static::$ResourceName;
    if (!empty($this->DependentResources) || !empty($this->Associations))
    {
      // ===============================================
      // ============ SHOW THE DEPENDENT OBJECTS  ======
      // ===============================================
      if (!empty($this->DependentResourceData))
      {
        $c= count($this->DependentResources);
        echo "<tr><td colspan=2><table>";
        foreach ($this->DependentResources as $dependentResourceName=>$dependencyResourceItem)
        {
            //Only populate dependent items that already exist.
            //  Adding items as dependencies is done on that item's form

            $dependentResourceObject =
                isset($this->DependentResourceData[$dependentResourceName]) ?
                $this->DependentResourceData[$dependentResourceName] : null;
            if ($dependentResourceObject)
            {
              $c = count($dependentResourceObject);

               if (   $c<100 &&
                      $dependentResourceName!='Storagebins' &&
                        $dependentResourceName!='Storagepallets' &&
                        $dependentResourceName!='Storage' &&
                        $dependentResourceName!='StorageItems'){
                  echo "<tr>";
                  echo "<td style='background-color: Darkgray; color:Black; padding:3px;'>
                        <label for='{$dependentResourceName}'>
                          <B>{$dependencyResourceItem['formlabel']}</B>
                        </label></td>";

                  echo "<td align=left style='background:LightBlue;'>";

                  $elementID = static::$ResourceName . $dependentResourceName;

                  echo $this->showAssociationsAsList(
                                            $elementID,
                                            $dependentResourceObject,
                                            $dependencyResourceItem["ListSize"],null,null,null,null,$this);

                  echo "</td>";
                  echo "</tr>";
                }
                else{

                }
            }
            else{
              Log::debug("There were no dependent resources for this record yet.");
            }
        }
        echo "</table></td></tr>";
      }
      // ===============================================
      // ============ SHOW THE ASSOCIATIONS ============
      // ===============================================
      if (!empty($this->Associations))
      {
          //Get the association records
          foreach ($this->AssociationData as $associativeCollectionName => $associationCollectionItem) {

            if ( !$isAdmin && ($associativeCollectionName=='receivers' && !$isClient)) {
              continue;
            }
            $foreignResources                 = $associationCollectionItem["ForeignResources"];
            $associativeTablePrimaryFieldName = $associationCollectionItem["associativeTablePrimaryFieldName"];

            foreach ($foreignResources as $foreignResourceName=>$associationObject) {

              $listSize             = $foreignResources[$foreignResourceName]["ListSize"];
              $foreignResourceLabel = $foreignResources[$foreignResourceName]["ForeignResourceLabel"];
              $linkedfieldName      = $foreignResources[$foreignResourceName]["LinkedFieldName"];
              $foreignResourceClassName = NAME_SPACE . "\\" . $foreignResourceName;
              $foreignResource      = new $foreignResourceClassName(); //create an object to pass to nav-bar

              $linkedResources      = $foreignResources[$foreignResourceName]["LinkedResources"];

              echo "<tr id='association-$associativeCollectionName'>";
              echo "<td style='background-color: #f2b21b; color:Black; padding:3px;'><B>$foreignResourceLabel</B></td>";

              // Show these related items

              echo "<td align=left>";

              $rowData = SessionManager::GetAssignableResources($associativeCollectionName);

              //Based on config, prepare the navigation bar
              $navbarData = array("associativeCollectionName"         => $associativeCollectionName,
                                  "foreignFieldName"                  => $linkedfieldName,
                                  "foreignResourceName"               => $foreignResourceName,
                                  "associativeTablePrimaryFieldName"  => $associationCollectionItem["associativeTablePrimaryFieldName"],
                                  "rowData"                           => $rowData
                                );

              $elementNameBase = $associativeCollectionName . $foreignResourceName;

              $navRowData = array(  $elementNameBase,
                                    $resourceName,
                                    $this->ID,
                                    $associativeCollectionName,
                                    $foreignResourceName,
                                    $associativeTablePrimaryFieldName,
                                    $linkedfieldName
                                  );

              //Build the nav-bar

              //Skip items that should be read only, meaning can not add here (StorageRequests, ShipRequests)
              $showOnly = ConfigurationManager::GetParameter('showOnlyAssociations');

              // TODO: this is a bad name

              // TODO: Get inarray to work ??
              $skip = false;
              $navbar = null;
              foreach ($showOnly as $r) {
                if ($r==$foreignResourceName){
                  $skip = true;
                }
              }
              $skip = \in_array($foreignResourceName, $showOnly);
              if ( !$skip){

                $navbar = $this->buildNavbar(Constants\UI_NavigationTypes::NAVBAR_RECORD_ASSOCIATION,
                                      $elementNameBase,
                                      $foreignResource,
                                      $navbarData
                                    );
              //  echo "will show $foreignResourceName";
                }

              //Push configs onto UI
              //// TODO: move this into more central place, get these scripts out of here !
              echo "<br>
                      <script>
                              PWH_UIService.ResourceConfig.Add('$resourceName');
                              PWH_UIService.ResourceConfig.Items.$resourceName.AddAssociation(
                                                  '$associativeCollectionName',
                                                  '$foreignResourceName',
                                                  '$associativeTablePrimaryFieldName',
                                                  '$linkedfieldName'
                                );
                      </script>";
              $extraRowData = null;//THIS IS NOT USED

              echo $foreignResource->showAssociationsAsList($elementNameBase,
                                        $linkedResources,
                                        $listSize,
                                        $navbar,
                                        "GetRecordItemDisassociateLink",
                                        $navRowData, $extraRowData, $this);

                            echo "</td>";
                            echo "</tr>";

            }//End of looping foreignresources
          }
      }
    }
  }

  /*
   Using a prepared DB statement, return one or more objects using current called class
  */
  static function GetInstancesUsingQuery($preparedStatementName, $preparedStatementValues){

    $resourceName = static::$ResourceName;
    $resourceClassName = NAME_SPACE . "\\" . $resourceName;
    $resources = [];
    $records = DataProvider::GET($preparedStatementName, $preparedStatementValues);

    if ( !empty($records))
    {
      foreach ($records as $rowData)
      {
        $resources[] = $resourceClassName::CreateFromRecord($rowData);
      }
    }
    return $resources;
  }
  /*
  Build and return HTML element for a nav-bar
  @param: $navbarData - Associative array, keys vary on $navbarType,
                      stored on DOM element
  */
  function buildNavbar($navbarType, $elementID, $resource, $navbarData=null){

    $selectElementName = $elementID . 'SELECT';
    $resourceName = static::$ResourceName;
    $resourceID = $this->ID;
    $resourceAction=null;
    $resourceActionString=null;

    switch ($navbarType) {
      case Constants\UI_NavigationTypes::NAVBAR_RECORD_NAV:

        break;
      case Constants\UI_NavigationTypes::NAVBAR_RECORD_LINKED:

        break;
      case Constants\UI_NavigationTypes::NAVBAR_RECORD_ASSOCIATION:


        $retHTML = "<form id='". $elementID . "NAV'>";
        $retHTML .= $resource->ShowSelectDropDownComponent(0, $navbarData['rowData'], $selectElementName);

        $retHTML .= "<button id='" . $elementID . "Submit'>add</button>";

        $retHTML .= "</form>";

        $resourceAction = "associate";

        //facilityowners/user
        $resourceActionItem = $navbarData['associativeCollectionName'] . "/" .
                              strtolower($navbarData['foreignResourceName']);



        $resourceActionString = "?" . $navbarData['foreignFieldName'] . "=";


        break;
      case Constants\UI_NavigationTypes::NAVBAR_RECORD_DISASSOCIATION:
        $retHTML = "NOT USED NOT USED";
        //// NOTE: NAVBAR IS NOT USED for the subnav, it is instead in UIManager::GetRecordItemDisassociateLink


        break;
      default:
        Log::error("Error with type for buildNavbar");

        break;
      }


      //TODO better/cleaner way to do this
      // ----- TODO TODO

      // --- ATTACH HANDLER FOR THIS NAVBAR ---

      // JQUERY - Attach a click handler to the nav submit just created
      ////api/resource/resourceid/resourceAction/resourceActionItem/actionitem2?FieldData
      $apiURL = ConfigurationManager::GetParameter("APIURL");
      if (1)
      {
          $associativeCollectionName  = $navbarData['associativeCollectionName'];
          $foreignResourceName        = $navbarData['foreignResourceName'];
          $foreignFieldName           = $navbarData['foreignFieldName'];
          $elementIDList              = $elementID . 'LIST';
          $elementIDNav               = $elementID . 'NAV';

          //BUILD THE API URL = start with host/api/resource/resourceID

          //// TODO: why doesn't the default selected items yield zero via jquery?

          $retHTML .=
            "<script>
            $('#" . $elementID . "Submit').click(
              function(){
                var selectedID = $('#" . $selectElementName . " :selected').val();

                if (isNaN(selectedID))
                  selectedID = 1;
                \n
                var apiURL = '" . $apiURL . "/" . strtolower($resourceName) . "/$resourceID";

                //ADD an action to the resource  /associate/
                if(!empty($resourceAction)){
                  $retHTML .= "/$resourceAction";
                }
                //ADD an ActionItem /associate/facilityowners/user
                if(!empty($resourceActionItem)){
                  $retHTML .= "/$resourceActionItem";
                }

                //ADD the QueryString /associate/facilityowners/user?userid={#}
                if(!empty($resourceActionString)){
                  $retHTML .= $resourceActionString . "' + selectedID;";
                }

          $retHTML .= "\n
                var callbackOrderedData = ['$elementID','$resourceName',$this->ID,'$associativeCollectionName', '$foreignResourceName','$foreignFieldName', selectedID];
                var callbackMethodName = 'CloudServiceResponseHandlers.associate';
                var parameterData = null;
                CloudService.POST('$elementID',
                                  apiURL, parameterData,
                                  callbackMethodName,
                                  callbackOrderedData);

                return false;

              });
              </script>";
        }


      return $retHTML;

  }
  /*
    Associate this record to another record in the DB associated table configuerd
    in the associativeCollectionName
    @param: $associativeCollectionName - Name of the Associative Collection in config
    @param: $fieldData - Data needed for the query (i.e. userid:2)
    @returns: associated primary id
  */
  function associate($associativeCollectionName, $foreignResourceName, $fieldData){

    // TODO: move this down to child class
    if (isset($fieldData['palletid'])){
       $fieldData['palletid']= intval($fieldData['palletid']);
     }

    $resourceName = static::$ResourceName;
    $resources = [];
    return DataProvider::ASSOCIATE($this, $associativeCollectionName, ucfirst($foreignResourceName), $fieldData);
  }
  /*
    Associate this record to another record in the DB associated table configuerd
    in the associativeCollectionName
    @param: $associativeCollectionName - Name of the Associative Collection in config
    @param: $fieldData - KV Pair of needed for the query (i.e. userid:2)
  */
  function disassociate($associativeCollectionName, $foreignResourceName, $fieldData){
    $resourceName = static::$ResourceName;
    $resources = [];

    // TODO: move this down to child class
    if (isset($fieldData['palletid'])){
      $fieldData['palletid'] = intval($fieldData['palletid']);
    }
    return DataProvider::DISASSOCIATE($this, $associativeCollectionName, ucfirst($foreignResourceName), $fieldData);
  }

  public function approve($userID){
    return DataProvider::Approve($this, $userID);
  }


}
