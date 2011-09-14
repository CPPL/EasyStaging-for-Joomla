<?php defined('_JEXEC') or die('Restricted access'); ?>
<form action="index.php" method="post" name="adminForm">
<div id="editcell">
    <table class="adminlist">
    <thead>
        <tr>
            <th width="5">
                <?php echo JText::_( 'NAME' ); ?>
            </th>
            <th>
                <?php echo JText::_( 'DESCRIPTION' ); ?>
            </th>
            <th>
                <?php echo JText::_( 'PUBLISHED' ); ?>
            </th>
        </tr>            
    </thead>
    <?php
    $k = 0;
    foreach ($this->items as &$row)
    {
        ?>
        <tr class="<?php echo "row" . $k; ?>">
            <td>
                <?php echo $row->name; ?>
            </td>
            <td>
                <?php echo $row->description; ?>
            </td>
            <td>
                <?php echo $row->published; ?>
            </td>
        </tr>
        <?php
        $k = 1 - $k;
    }
    ?>
    </table>
</div>
 
<input type="hidden" name="option" value="com_easystaging" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="controller" value="easystaging" />
 
</form>
