<div>
<? echo $field_desc; ?>
<select name="<? echo $prefix.$field_name.$suffix; ?>" >
<? foreach($model_form_data as $option)
{
    echo "<option value='{$option['id']}'";
    if($value==$option['id'])
        echo " selected='selected' ";
    echo ">{$option['name']}</option>";

}
?>
</select>
</div>