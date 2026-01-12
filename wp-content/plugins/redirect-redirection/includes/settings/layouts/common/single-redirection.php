<?php
if ( ! defined( "ABSPATH" ) ) {
	exit();
}
$id = (int) $id;
?>
<div class="flex-table__col ir-redirect-<?php echo $id; ?>">
    <div class="flex-table__row">
        <span class="flex-table__row-column row-column d-flex align-items-center">
            <!-- Checkbox for mobile -->
            <span class="d-lg-none flex-grow-1">
                <label for="switch-1_<?php echo $id; ?>" class="custom-switch">
                    <input type="checkbox" id="switch-1_<?php echo $id; ?>"
                          
                           class="ir-instant-edit-redirect ir-instant-edit-status ir-instant-edit-status-<?php echo $id; ?>" <?php esc_attr_e( $status ); ?> data-db-id="<?php echo $id; ?>">
                        <div class="custom-switch-slider round">
                            <span class="on"><?php _e(  "On", "redirect-redirection"  ); ?></span>
                            <span class="off"><?php _e(  "Off", "redirect-redirection"  ); ?></span>
                        </div>
                </label>
            </span>
            <input type="checkbox"
                   class="ir-redirect-chk ir-redirect-chk-<?php echo $id; ?>" <?php esc_attr_e( $checked ); ?> data-db-id="<?php echo $id; ?>">
        </span>
        <!-- Checkbox for desktop -->
        <span class="flex-table__row-column row-column d-none d-lg-flex align-items-center">
            <label for="switch-2_<?php echo $id; ?>" class="custom-switch">
                <input type="checkbox"
                       id="switch-2_<?php echo $id; ?>" <?php esc_attr_e( $status ); ?> class="ir-instant-edit-redirect ir-instant-edit-status ir-instant-edit-status-<?php echo $id; ?>"
                       data-db-id="<?php echo $id; ?>" data-db-column="status">
                    <div class="custom-switch-slider round">
                        <span class="on"><?php _e( "On", "redirect-redirection" ); ?></span>
                        <span class="off"><?php _e( "Off", "redirect-redirection" ); ?></span>
                    </div>
            </label>
        </span>
        <span class="flex-table__row-column row-column d-block">
            <span class="row-column__input-label d-lg-none"><?php _e( "Redirect from", "redirect-redirection" ); ?></span>
            <?php if ( $redirectionType === self::TYPE_REDIRECTION ) { ?>
                <div class="row-column__input-group table-input-group">
                    <input class="flex-table__input table-input-group__input ir-instant-edit-redirect ir-instant-edit-from ir-instant-edit-from-<?php echo $id; ?> ir-scroll-to-right"
                           type="text"
                           value="<?php esc_attr_e( $from ); ?>" <?php esc_attr_e( $fromDisabled ); ?> title="<?php esc_html_e( $fromTitle ); ?>"
                           data-db-id="<?php echo $id; ?>" data-db-column="from">
                </div>
	            <?php
            } else {
	            foreach ( $metas[ self::META_KEY_CRITERIAS ] as $key => $criteria ) {
		            $from      = $criteria["value"];
		            $fromIndex = array_search( $metas[ self::META_KEY_CRITERIAS ][ $key ]["criteria"], array_column( IrrPRedirection::$CRITERIAS, "option" ) );
		            $fromTitle = empty( IrrPRedirection::$CRITERIAS[ $fromIndex ]["text"] ) ? "" : IrrPRedirection::$CRITERIAS[ $fromIndex ]["text"];
		            $style     = ( $key > 0 ) ? "margin-top:3px;" : "";
		            ?>
                    <div class="row-column__input-group table-input-group" style="<?php esc_html_e( $style ); ?>;">
                        <input class="flex-table__input table-input-group__input ir-instant-edit-redirect ir-instant-edit-from ir-instant-edit-from-<?php echo $id; ?> ir-scroll-to-right"
                               type="text" value="<?php esc_attr_e( $from ); ?>" <?php esc_attr_e( $fromDisabled ); ?> title="<?php esc_html_e( $fromTitle ); ?>"
                               data-db-id="<?php echo $id; ?>" data-db-column="from">
                    </div>
		            <?php
	            }
            }
            ?>
        </span>
        <span class="flex-table__row-column row-column">
            <div class="row-column__arrow-svg d-flex align-items-center">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19.829 9.5547L13.8293 2.88839C13.7013 2.74706 13.5227 2.66707 13.3334 2.66707H9.33358C9.07093 2.66707 8.83227 2.82173 8.72428 3.06171C8.61762 3.30303 8.66162 3.58435 8.83761 3.77901L14.436 10L8.83761 16.2197C8.66162 16.4157 8.61628 16.697 8.72428 16.937C8.83227 17.1783 9.07093 17.3329 9.33358 17.3329H13.3334C13.5227 17.3329 13.7013 17.2516 13.8293 17.113L19.829 10.4466C20.057 10.1933 20.057 9.80668 19.829 9.5547Z"
                          fill="currentColor"/>
                    <path d="M11.1628 9.5547L5.16314 2.88839C5.03514 2.74706 4.85649 2.66707 4.66716 2.66707H0.66738C0.404728 2.66707 0.166074 2.82173 0.0580799 3.06171C-0.048581 3.30303 -0.00458339 3.58435 0.171407 3.77901L5.76977 10L0.171407 16.2197C-0.00458339 16.4157 -0.0499143 16.697 0.0580799 16.937C0.166074 17.1783 0.404728 17.3329 0.66738 17.3329H4.66716C4.85649 17.3329 5.03514 17.2516 5.16314 17.113L11.1628 10.4466C11.3908 10.1933 11.3908 9.80668 11.1628 9.5547Z"
                          fill="currentColor"/>
                </svg>
            </div>
        </span>
        <span class="flex-table__row-column row-column d-block">
            <span class="row-column__input-label d-lg-none"><?php _e( "To a specific URL", "redirect-redirection" ); ?></span>
            <div class="row-column__input-group table-input-group">
                <?php if ( isset( $metas["action"]["name"] ) ) { ?>
	                <?php if ( $metas["action"]["name"] != 'urls-with-removed-string' ) { ?>
                        <input class="flex-table__input table-input-group__input ir-instant-edit-redirect ir-instant-edit-to ir-instant-edit-to-<?php echo $id; ?> <?php echo ( $to ) ? 'ir-with-data' : 'ir-without-data'; ?> ir-scroll-to-right"
                               type="text" value="<?php esc_attr_e( $to ); ?>" <?php esc_attr_e( $toDisabled ); ?>  title="<?php esc_html_e( $toTitle ); ?>"
                               data-db-id="<?php echo $id; ?>" data-db-column="to">
	                <?php } ?>
                <?php } else { ?>
                    <input class="flex-table__input table-input-group__input ir-instant-edit-redirect ir-instant-edit-to ir-instant-edit-to-<?php echo $id; ?> <?php echo ( $to ) ? 'ir-with-data' : 'ir-without-data'; ?> ir-scroll-to-right"
                           type="text"
                           value="<?php esc_attr_e( $to ); ?>" <?php esc_attr_e( $toDisabled ); ?>  title="<?php esc_html_e( $toTitle ); ?>"
                           data-db-id="<?php echo $id; ?>" data-db-column="to">
                <?php } ?>
                        </div>
                        </span>
        <span class="flex-table__row-column row-column">
                            <span class="row-column__label d-lg-none flex-grow-1">
                                <?php _e( "Date added", "redirect-redirection" ); ?>
                            </span>
                            <span>
                                <?php echo date( "d/m/Y", $timestamp ); ?>
                            </span>
                        </span>
        <span class="flex-table__row-column row-column">
                            <span class="row-column__label d-lg-none flex-grow-1">
                                <?php _e( "Type", "redirect-redirection" ); ?>
                            </span>
                            <span id="ir-redirect-code-<?php echo $id; ?>">
                                <?php echo (int) $redirectCode; ?>
                            </span>
                        </span>
        
                        <span class="flex-table__row-column row-column">
                            <span class="row-column__label d-lg-none flex-grow-1">
                                <?php _e( "Used", "redirect-redirection" ); ?>
                            </span>
                            <span id="ir-redirect-code-<?php echo $id; ?>">
                                <?php echo $usage_count; ?>
                            </span>
                        </span>
        <span class="flex-table__row-column row-column d-flex align-items-center">
                            <span class="row-column__label d-lg-none flex-grow-1">
                                <?php _e( "Actions", "redirect-redirection" ); ?>
                            </span>
                            <button class="ir-edit-redirect <?php esc_attr_e( $editClass ); ?>"
                                    data-db-id="<?php echo $id; ?>" <?php echo $isIncExcEnabled === false ? 'data-incexc="true"' : ""; ?>>
                                <img src="<?php echo plugins_url( IRRP_DIR_NAME . "/assets/css/assets/images/edit.svg" ); ?>"
                                     alt="<?php _e( "edit row", "redirect-redirection" ); ?>">
                            </button>
                            <button class="ir-delete-confirmation-show" style="margin-left: 7px"
                                    data-db-id="<?php echo $id; ?>">
                                <img src="<?php echo plugins_url( IRRP_DIR_NAME . "/assets/css/assets/images/close.svg" ); ?>"
                                     alt="<?php _e( "close row", "redirect-redirection" ); ?>">
                            </button>
                        </span>
    </div>
</div>