<?php
include_once dirname(__FILE__)."/parsel_admin_header.php";

$store_id         = "";
$show_button      = "no";
$button_bg        = "";
$button_fg        = "white";
$store_url        = "";
$debug            = "no";
$use_http         = "no";
$render_engine    = "";
$analytics        = "";

if( !empty($data['parsel_options']) ):
	$store_id         = htmlentities(@$data['parsel_options']['parsel_store_id']                 , ENT_QUOTES);
	$show_button      = htmlentities(@$data['parsel_options']['parsel_add_store']                , ENT_QUOTES);
	$button_bg        = htmlentities(@$data['parsel_options']['parsel_button_bg']                , ENT_QUOTES);
	$button_fg        = htmlentities(@$data['parsel_options']['parsel_button_fg']                , ENT_QUOTES);
	$store_url        = htmlentities(@$data['parsel_options']['parsel_store_url']                , ENT_QUOTES);
    $analytics        = htmlentities(@$data['parsel_options']['parsel_analytics']                , ENT_QUOTES);

	$debug            = htmlentities(@$data['parsel_options']['parsel_support']['debug']         , ENT_QUOTES);
    $use_http         = htmlentities(@$data['parsel_options']['parsel_support']['use_http']      , ENT_QUOTES);
	$render_engine    = htmlentities(@$data['parsel_options']['parsel_support']['render_engine'] , ENT_QUOTES);

endif;
?>

<h2>Parsel Store Setting</h2>

<?php if( isset($data['message']) ):?>
	<div id="setting-error-settings_updated" class="<?php echo isset($data['error'])?"error":"updated"?> settings-error">
		<p><strong> <?php echo $data['message']?> </strong></p>
	</div>
<?php endif ?>

<form action="<?php echo str_replace('%7E', '~', $_SERVER['REQUEST_URI']); ?>" method="post" accept-charset="utf-8">
	<table class="form-table">
		<tbody>

			<tr valign='top'>
				<th scope='row'>
					<label>Parsel Store ID</label>
				</th>
				<td>
					<input type="text" name="parsel_store_id" id="id" maxlength="50" value="<?php echo $store_id?>" >
				</td>
			</tr>
		</tbody>
	</table>

	<h3 class="title">Parsel Store Button</h3>
	<table class='form-table'>
		<tbody>
			<tr valign='top'>
				<th scope='row'>
					<label for="">Show Store Button</label>
				</th>
				<td>
					<label><input type="radio" name="parsel_add_store" value="yes" <?php echo $show_button=="yes"?"checked":""?> > Yes</label><br/>
					<label><input type="radio" name="parsel_add_store" value="no" <?php echo $show_button=="no"?"checked":""?> > No</label>
					<p class="description">Adds a store button to your wordpress website</p>
				</td>
			</tr>

			<tr valign='top'>
				<th scope='row'>
					<label for="">Store URL</label>
				</th>
				<td>
					<input type="text" name="parsel_store_url" id="store_url" value="<?php echo $store_url?>" >
				</td>
			</tr>

			<tr valign='top'>
				<th scope='row'>
					<label for="">Text Color</label>
				</th>
				<td>
                    <select name="parsel_button_fg" id="fg">
                        <option value="black" <?php echo ($button_fg=="black") ? "selected" : ""?> >Black</option>
                        <option value="white" <?php echo ($button_fg=="white") ? "selected" : ""?> >White</option>
                    </select>
				</td>
			</tr>

			<tr valign='top'>
				<th scope='row'>
					<label for="">Background Color</label>
				</th>
				<td>
					<input type="text" name="parsel_button_bg" id="bg" maxlength="7" value="<?php echo $button_bg ?>" >
				</td>
			</tr>

            <tr valign='top'>
                <th scope='row'>
                    <label for="">Parsel Analytics</label>
                </th>
                <td>
                    <input type="text" name="parsel_analytics" id="analytics" maxlength="20" value="<?php echo $analytics ?>" >
                </td>
            </tr>


        </tbody>

	</table>

	<h3 class="title">Parsel Store Support </h3> <a href="javascript://" class='js-parsel-debug'>debug</a>
	<table class="form-table parsel-debug">
		<tbody>
			<?php foreach($data['parsel_support'] as $config=>$support): ?>
				<tr valign='top'>
					<th scope='row'>
						<label><?php echo $config ?></label>
					</th>
					<td>
						<?php echo $support ? "<span class='parsel-config-yes'>Yes</span>" : "<span class='parsel-config-yes'>No</span>" ?>
					</td>
				</tr>
			<?php endforeach; ?>

            <tr valign='top'>
                <th scope='row'>
                    <label for="">Use HTTP</label>
                </th>
                <td>
                    <label><input type="checkbox" name="parsel_use_http" value="yes" <?php echo $use_http=="yes"?"checked":""?> > Yes</label><br/>
                </td>
            </tr>


            <tr valign='top'>
				<th scope='row'>
					<label for="">Debug</label>
				</th>
				<td>
					<label><input type="radio" name="parsel_debug" value="yes" <?php echo $debug=="yes"?"checked":""?> > Yes</label><br/>
					<label><input type="radio" name="parsel_debug" value="no" <?php echo $debug=="no"?"checked":""?> > No</label>
				</td>
			</tr>

			<tr valign='top'>
				<th scope='row'>
					<label for="">Force Render Engine</label>
				</th>
				<td>
					<input type="text" name="parsel_render_engine" id="render_engine" maxlength="50" value="<?php echo $render_engine ?>" >
				</td>
			</tr>
		</tbody>
	</table>


	<p class="submit">
		<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
	</p>

</form>

<?php
include_once dirname(__FILE__)."/parsel_admin_footer.php";
?>
