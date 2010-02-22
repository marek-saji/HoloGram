<div>
<? echo $field_desc; ?>
<select name="<? echo $prefix.$suffix.'['.$field_name.']'; ?>" multiple="multiple" >
<?
$i=0;
foreach($model_form_data as $option)
{
    $i++;
    if($value=='')
        $value=array();
    if(!is_array($value))
        $value = array($value);
    echo "<option value='{$option['id']}'";
    if(in_array($option['id'],$value))
        echo " selected='selected' ";    
    echo ">{$option['name']}</option>";
}
?>
</select>
<?

foreach($model_form_data as $option)
{
    if($value=='')
        $value=array();
    if(!is_array($value))
        $value = array($value);
    if(in_array($option['id'],$value))
    {
        echo "<input type='hidden' name='".$prefix.$i.$suffix."[".$field_name."]' value='{$option['id']}'>";
        echo "<input type='hidden' name='".$prefix.$i.$suffix."[_action]' value='delete'>";
        $i++;
    }
        
}
?>
</div>
