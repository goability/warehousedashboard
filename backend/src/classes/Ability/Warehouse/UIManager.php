<?php
namespace Ability\Warehouse;

class UIManager
{

 /*
  Build a click handler and add it to the specific resource

  @param: $elementIDBasename
            - Base id of the html LIST element.  'LIST' is concatted to this
            This is used to remove the item from the list ,
  @param: $primaryResourceName
              - name of the resource owning the association - storagefacility,
  @param: $primaryRecordID
              - record of the primary resource - facilityid,
  @param: $associativeCollectionName
              - name of the associative collection
               NOTE that this is in config, and not the table name i.e.  facilityowners
  @param: $foreignResourceName
              - name of the linked resource - user,
  @param: $associativeTablePrimaryFieldName
              - associative table col representing primary field - facilityid
  @param: $foreignResourceIndexFieldName
              - associative table col representing foreign field -- userid
  @param: $foreignResourceID
              - recordid of the foreign linked object
 */
  public static function GetRecordItemDisassociateLink( $elementIDBasename,
                                                        $primaryResourceName,
                                                        $primaryRecordID,
                                                        $associativeCollectionName,
                                                        $foreignResourceName,
                                                        $associativeTablePrimaryFieldName,
                                                        $foreignResourceIndexFieldName,
                                                        $foreignResourceID)
  {
    $apiURL = ConfigurationManager::GetParameter("APIURL");
    return "<i class='fa fa-trash nav-record-item-disassociate'
                id='nav-record-item-disassociate-$foreignResourceID'
                onclick =\"

                    PWH_UIService.Disassociate('" .  $elementIDBasename . "', '" .
                          $apiURL . "/" . $primaryResourceName . "'," .
                          "'$associativeCollectionName',
                          '$foreignResourceName',
                          '$foreignResourceIndexFieldName',
                          '$primaryRecordID',
                          '$foreignResourceID');
                          \";></i>";
  }


}
