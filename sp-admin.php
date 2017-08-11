<div class="wrap">
    <h2>Anti Adblock Ads</h2>
<p><a href="http://slimspots.com/register/1463"><img src="http://slimspots.org/adview.gif"></a></p>
    <br />
    <br />
    <div class="like-fb-left">
	<strong><u>General Settings</u></strong>
        <fieldset>
            
            <form method="post" action="options.php">
                <?php
                settings_fields( 'slimpots-group' );
                do_settings_sections( 'slimpots-group' );
                ?>

                <table class="form-table">
				 <tr valign="top">
                        <th scope="row">Your Slimspots User ID:</th>
                        <td><input type="text" name="spID" value="<?php echo esc_attr( get_option('spID') ); ?>" />
						<p><small>Show your Referal link for this one in your <a href="http://slimspots.com/register/1463">Slimspots Account</a>!  (http://slimspots.com/register/<strong>XXX</strong>)</small>
							</p></td>
                    </tr>
					
                    <tr valign="top">
                        <th scope="row" style="padding-bottom:0;">Your Anti-AdBlocker KEY:</th>
                        <td style="padding-bottom:0;"><input type="text" name="spKEY" value="<?php echo esc_attr( get_option('spKEY') ); ?>" style="width:300px;" />
						<p><small>Don't have a KEY? Ask your Slimspots Account-Manager!</small></p></td>
						
                    </tr>                    
                   
                </table>                
                
                <?php submit_button(); ?>
            </form>
        </fieldset>        
    </div>    
    
</div>
