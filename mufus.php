<?php

/**
 * assets/functions/mm2_mufusli.php
 *
 * Generates MuFuSli
 *
 * @category    Template Functions
 * @package     MM2
 * @author      wlautner
 * @version     Release: 0.0.1
 * @since       2019-04-02 (19)
 *
 * changes:
 *      2021-10-29 If User is Editor, reset Security
 *      2021-05-11 Added EndDate for a MuFuSli Element, Check for Date on a DPO Element
 *      2020-04-21 Adding Borlabs Vimeo Cookie check
 *      2020-03-23 Display Headline if no ArticleImage
 *      2020-03-18 Fixed Alternative Image Bug
 *
 */

$lMufusliCounter = 0;

/**
 * @param $aPostId
 */
function mm2_MuFuSliPrintFromPostId($aPostId)
{
	// Get the mufusli elements
	$lElements = get_field('mufusli_elements', $aPostId);
	
	// Add extra fields to elements
	$lOptions['mufusli_title'] = get_field('mufusli_title', $aPostId);
	$lOptions['mufusli_infobox'] = get_field('mufusli_infobox', $aPostId);
	$lOptions['mufusli_backgroundcolor'] = get_field('mufusli_backgroundcolor', $aPostId);
	$lOptions['mufusli_layout'] = get_field('mufusli_layout', $aPostId);
	$lOptions['mufusli_css'] = get_field('mufusli_css', $aPostId);
	$lOptions['mufusli_detailbutton'] = get_field('mufusli_detailbutton', $aPostId);
	$lOptions['mufusli_detailbutton_text'] = get_field('mufusli_detailbutton_text', $aPostId);
	$lOptions['mufusli_detailbutton_link'] = get_field('mufusli_detailbutton_link', $aPostId);
	$lOptions['mufusli_detailbutton_linktarget'] = get_field('mufusli_detailbutton_linktarget', $aPostId);
	$lOptions['mufusli_visibility_mode'] = get_field('mufusli_visibility_mode', $aPostId);
	$lOptions['mufusli_visibility_grouprights'] = get_field('mufusli_visibility_grouprights', $aPostId);
	$lOptions['mufusli_visibility_logintext'] = get_field('mufusli_visibility_logintext', $aPostId);
	
	// Print the MuFuSli
	mm2_MuFuSliPrint($lOptions, $lElements);
}

/**
 * @param $aOptions
 * @param $aElements
 */
function mm2_MuFuSliPrint($aOptions, $aElements) {
	// Increase the counter
	global $lMufusliCounter;
	$lMufusliCounter++;

	$lMufusliIsInfoBox = $aOptions['mufusli_infobox'] == '1';
	
	if ($aElements || $lMufusliIsInfoBox )
	{
		// Get the settings
		$lMufusliTitle = $aOptions['mufusli_title'];
		$lBackgroundColor = $aOptions['mufusli_backgroundcolor'];
		$lBackgroundColorRgba1 = hex2rgba($lBackgroundColor, 0);
		$lBackgroundColorRgba2 = hex2rgba($lBackgroundColor, 1);
		
		$lVisibilityMode = $aOptions['mufusli_visibility_mode'];
		$lGroupRights = $aOptions['mufusli_visibility_grouprights'];
		$lLoginText = $aOptions['mufusli_visibility_logintext'];

		$lMufusliIsInfoBoxContent = $aOptions['mufusli_infobox_content'];
		
		// Get the user profession if needed
		$lProfession = 'none';
		if ( is_user_logged_in() )
        {
			$lMedmediaConfig = new MM_Configs();
			$lProfileFields = $lMedmediaConfig->get_xProfileFieldsConfig();
			$lProfession = xprofile_get_field_data($lProfileFields['professional']['id'], get_current_user_id());

			// Check if user is "editor" -> no security
			if (current_user_can('editor'))
			{
				$lVisibilityMode = 'public';
			}
		}
		
		// Is the mufusli visible, either by security or by info-box mode
		$lMufusliContentVisible = !$lVisibilityMode || $lVisibilityMode == 'public' || ($lVisibilityMode == 'role' && strpos($lGroupRights, $lProfession) > 0);
		
		$lLayoutCss = '';
		$lLayoutTag = ' columns="4"';
		$lLayout = $aOptions['mufusli_layout'];
		if ($lLayout == '2') {
			$lLayoutCss = ' columns_2';
			$lLayoutTag = ' columns="2"';
		}
		$lCss = $aOptions['mufusli_css'];
		if ($lCss)
			$lCss = ' ' . $lCss;
		?>
        <style>
            .wrapper_mufusli .mufusli .carousel .mufusli_<?php echo($lMufusliCounter); ?> .info::after {
                background: linear-gradient(180deg, <?php echo($lBackgroundColorRgba1); ?> 0%, <?php echo($lBackgroundColorRgba2); ?> 100%);
            }
        </style>
        <div class="wrapper_mufusli<?php echo($lCss . $lLayoutCss); ?>" style="background-color: <?php echo($lBackgroundColor); ?>">
			<?php
			if (!empty($lMufusliTitle)) {
				?>
                <div class="grid-container">
                    <h2><?php echo($lMufusliTitle); ?></h2>
                </div>
				<?php
			}
			if ($lMufusliContentVisible)
            {
				// Check if info-box
				if ($lMufusliIsInfoBox) {
					?>
                    <div class="mufusli" mufusliid="<?php echo($lMufusliCounter); ?>"<?php echo($lLayoutTag); ?>>
                        <div class="grid-container">
							<?php
							echo($lMufusliIsInfoBoxContent);
							
							// Is there a detail button
							$lShowDetailsButton = $aOptions['mufusli_detailbutton'] == '1';
							if ($lShowDetailsButton) {
								$lDetailButtonText = $aOptions['mufusli_detailbutton_text'] . ' &raquo;';
								$lDetailButtonLink = $aOptions['mufusli_detailbutton_link'];
								$lDetailButtonTarget = $aOptions['mufusli_detailbutton_linktarget'];
								if (!$lDetailButtonTarget)
									$lDetailButtonTarget = '_self';
								?>
                                <p class="more" style="position: inherit; margin-top: 12px;">
                                    <a href="<?php echo($lDetailButtonLink); ?>" target="<?php echo($lDetailButtonTarget); ?>"><?php echo($lDetailButtonText); ?></a>
                                </p>
								<?php
							}
							?>
                        </div>
                    </div>
					<?php
				} else {
					// Normal MuFusLi :)
					?>
                    <div class="mufusli" mufusliid="<?php echo($lMufusliCounter); ?>"<?php echo($lLayoutTag); ?>>
                        <div class="grid-container">
                            <div class="carousel">
								<?php
								$lElementsCount = 0;
								
								// Get Todays Date
								$lDateToday = date('Ymd');
                                var_dump($lDateToday);
								foreach ($aElements as $lElement)
								{
									$lElementsCount++;
									$lCellCss = 'cell mufusli_' . $lMufusliCounter . ' hidden';
									$lElementCss = '';
									$lElementType = $lElement['acf_fc_layout'];


$mufusli_elements_taxonomy = $lElement['mufusli_elements_article_end_date'];

var_dump($mufusli_elements_taxonomy);
									switch ($lElementType) {
										// mufusli_elements_article
										case 'mufusli_elements_article':
											// Get End date
											$lEndDate = $lElement['mufusli_elements_article_end_date'];

											if ( $lEndDate && !empty($lEndDate ))
											{
												if ( $lEndDate < $lDateToday )
												{
													// continue;
													break;
												}
											}
											// Get the fields for the article element
											$lArticleId = $lElement['mufusli_elements_article_id'];

											$lArticle = get_post($lArticleId);
											$lArticelImageTag = mm2_GetImageCode(get_post_thumbnail_id($lArticle->ID), '270', true);
											
											// Check if alternate image for this article
											$lAlternativeArticleImageId = get_field(MM2_ARTICLE_IMAGESQUARE, $lArticle->ID);
											if ($lAlternativeArticleImageId) {
												$lArticelImageTag = mm2_GetImageCode($lAlternativeArticleImageId, '270', true);
											}
											
											$lArticelImageLargeTag = '';
											$lArticleUrl = get_post_permalink($lArticle->ID);
											
											// Get the target and if necessary, change the link
											$lArticleTarget = $lElement['mufusli_elements_article_target'];
											$lDoLightbox = false;
											if (!empty($lArticleTarget) && $lArticleTarget == 'lightbox') {
												$lArticleUrl = "javascript:openDarkbox('darkbox_" . $lArticleId . "')";
												$lArticelImageLargeTag = mm2_GetImageCode(get_post_thumbnail_id($lArticle->ID), '2048', false);
												$lArticleTarget = '';
												$lDoLightbox = true;
											}
											
											// Get the post type
											$lPostType = get_post_type($lArticle->ID);
											// Title
											$lArticleTitle = $lElement['mufusli_elements_article_title'];
											if (empty($lArticleTitle)) {
												if ($lPostType == DPO_CPT_BASE_NAME) {
													$lArticleTitle = get_field('easydoc_titel', $lArticle->ID);
												} else {
													$lArticleTitle = $lArticle->post_title;
												}
											}
											
											// Check if article itself has an alternate subtitle
											$lArticleInfoTitle = get_field('mm2_infotitle', $lArticle->ID);
											
											$lAlternativeArticleInfoTitle = $lElement['mufusli_elements_article_infotitle'];
											if (!empty($lAlternativeArticleInfoTitle)) {
												$lArticleInfoTitle = $lAlternativeArticleInfoTitle;
											}
											
											// Info title
											if (empty($lArticleInfoTitle)) {
												$lTaxonomyName = '';
												// Depending on post type, get the taxonomy name
												switch ($lPostType) {
													case NEXTDOC_CPT_BASE_NAME:
														$lTaxonomyName = NEXTDOC_TAX_BASE_NAME;
														break;
													case RELATUSMED_CPT_BASE_NAME:
														$lTaxonomyName = RELATUSMED_TAX_BASE_NAME;
														break;
													case RELATUSPHARM_CPT_BASE_NAME:
														$lTaxonomyName = RELATUSPHARM_TAX_BASE_NAME;
														break;
													case CONGRESSXPRESS_CPT_BASE_NAME:
														$lTaxonomyName = CONGRESSXPRESS_TAX_BASE_NAME;
														break;
													case IMFOKUSARTICLE_CPT_BASE_NAME:
														$lTaxonomyName = IMFOKUSARTICLE_TAX_BASE_NAME;
														break;
													default:
														$lTaxonomyName = '';
												}
												
												if (!empty($lTaxonomyName)) {
													$lArticleInfoTitle = mm2_helpers_GetArticleTermsAsList($lArticle->ID, $lTaxonomyName);
												} else {
													$lArticleInfoTitle = '&nbsp;';
													
													// Check if DFP article
													if ($lPostType == DPO_CPT_BASE_NAME) {
														$lArticlePoints = get_field('easydoc_points', $lArticle->ID);
														if ($lArticlePoints > 1)
															$lArticlePointsExtension = 'e';
														$lArticleInfoTitle = $lArticlePoints . ' DFP-Punkt' . $lArticlePointsExtension;
														$lArticleGroup = get_field('easydoc_zielgruppe', $lArticle->ID);
														if ($lArticleGroup != 'Arzt')
															$lArticleInfoTitle = $lArticlePoints . ' Punkt' . $lArticlePointsExtension . ' für ' . $lArticleGroup;
													}
												}
											}

											// Alternative Image
											$lAlternativeArticleImageId = $lElement['mufusli_elements_article_image_id'];
											echo("<!-- IMGID: " . $lAlternativeArticleImageId . ' -->');
											if ($lAlternativeArticleImageId && !empty($lAlternativeArticleImageId)) {
												$lArticelImageTag = mm2_GetImageCode($lAlternativeArticleImageId, '270', true);
											}

											// Subtitle
											$lArticleSubTitle = $lElement['mufusli_elements_article_subtitle'];
											if (empty($lArticleSubTitle))
                                            {
												// Check if DFP article
												if ($lPostType == DPO_CPT_BASE_NAME)
												{
													$lArticleDate = DateTime::createFromFormat("Ymd", get_field('easydoc_validUntil', $lArticle->ID));
													if ($lArticleDate)
													{
														// Continue if Course is not Active anymore
														if ( $lArticleDate->format('Ymd') < $lDateToday )
													{
															// continue;
															break;
														}
														$lArticleSubTitle = 'Gültig bis ' . $lArticleDate->format('d.m.Y');
													}
												} else {
													$lArticleSubTitle = date('j.n.Y', strtotime($lArticle->post_date));
												}
											}

											// DPO article
											if ($lPostType == DPO_CPT_BASE_NAME) {
												$lArticelImageTag = get_field('easydoc_startimage', $lArticle->ID);
												if ($lArticelImageTag != false) {
													$lArticelImageTag = '<img src="' . $lArticelImageTag['url'] . '">';
												}
												
												$lArticleDfpIcon = get_field('easydoc_icon', $lArticle->ID);
												if ($lArticleDfpIcon)
													$lArticleDfpIcon = '<img src="' . $lArticleDfpIcon['url'] . '">';
											}

											// Article Icon
											$lArticleIconTag = '';
											$lArticleIcon = get_field('mm2_article_icon', $lArticle->ID);
											if ($lArticleIcon) {
												$lArticleIconTag = '<div class="article_icon">' . $lArticleIcon . '</div>';
											}
											
											// Check if paid activation
											$lArticleIsPaidactivationBackgroundColor = $lBackgroundColor;
											$lArticleIsPaidactivationTextColor = '#FFFFFF';
											$lArticleIsPaidactivation = $lElement['mufusli_elements_article_paidactivation'] == '1';
											if ($lArticleIsPaidactivation) {
												$lElementCss = ' nohover paidactivation mufusli_' . $lMufusliCounter . '_' . $lElementsCount;
												$lArticleIsPaidactivationBackgroundColor = $lElement['mufusli_elements_article_paidactivation_backgroundcolor'] ?? $lArticleIsPaidactivationBackgroundColor;
												$lArticleIsPaidactivationTextColor = $lElement['mufusli_elements_article_paidactivation_textcolor'] ?? $lArticleIsPaidactivationTextColor;
												
												$lBackgroundColorRgba1 = hex2rgba($lArticleIsPaidactivationBackgroundColor, 0);
												$lBackgroundColorRgba2 = hex2rgba($lArticleIsPaidactivationBackgroundColor, 1);
											}

											// Teasertext
											$lArticleTeaserText = $lElement['mufusli_elements_article_teaser'];
											if (empty($lArticleTeaserText)) {
												// Get more Excerpt text if no image
												if (empty($lArticelImageTag)) {
													$lArticleTeaserText = mm2_helpers_GetArticleTeaser($lArticle, 'teaser-more');
												} else {
													$lArticleTeaserText = mm2_helpers_GetArticleTeaser($lArticle);
												}
											}
											?>
                                            <a href="<?php echo($lArticleUrl); ?>" target="<?php echo($lArticleTarget) ?>">
                                                <div class="<?php echo($lCellCss); ?>">
                                                    <div class="article<?php echo($lElementCss); ?>">
														<?php
														
														//
														// ArticleImage
														//
														$_boolean_headline = false;
														if (!empty($lArticelImageTag)) {
															//
															$_boolean_headline = true;
															?>
                                                            <div class="img">
															<?php
															echo($lArticelImageTag);
															if (!empty($lArticleDfpIcon)) {
																?>
                                                                <div class="dfp_icon">
																	<?php echo($lArticleDfpIcon); ?>
                                                                </div>
																<?php
															}
															
															if (!empty($lArticleIconTag)) {
																echo($lArticleIconTag);
															}
															?>
                                                            </div><?php // .img
															?>
															<?php
														} // endif has ArticleImage
														
														?>
                                                        <div class="info <?php if (!$_boolean_headline) {
															echo('teaser-more');
														} ?>" <?php if ($lArticleIsPaidactivation)
															echo(' style="color: ' . $lArticleIsPaidactivationTextColor . '"'); ?>>
                                                            <span class="infotitle"><?php echo($lArticleInfoTitle); ?></span>
															<?php
															echo mm2_helpers_headline($lArticleTitle, 'h3');
															?>
                                                            <span class="subtitle"><?php echo($lArticleSubTitle); ?></span>
                                                            <p><?php echo($lArticleTeaserText); ?></p>
                                                        </div>
														<?php
														if ($lArticleIsPaidactivation) {
															?>
                                                            <div class="paidactivation_hint" style="background-color: <?php echo($lArticleIsPaidactivationBackgroundColor); ?>">
                                                                <span style="color: <?php echo($lArticleIsPaidactivationTextColor); ?>">Entgeltliche Einschaltung</span>
                                                            </div>
                                                            <style>
                                                                .wrapper_mufusli .mufusli .carousel .mufusli_<?php echo($lMufusliCounter); ?>_<?php echo($lElementsCount); ?> .info::after {
                                                                    background: linear-gradient(180deg, <?php echo($lBackgroundColorRgba1); ?> 0%, <?php echo($lBackgroundColorRgba2); ?> 100%);
                                                                }
                                                            </style>
															<?php
														}
														?>
                                                    </div>
                                                </div>
                                            </a>
											<?php

											// Is there a darkbox needed?
											if ($lDoLightbox) {
												mm2_DarkboxPrint($lArticleId, $lArticelImageLargeTag, $lArticleTitle, wpautop($lArticle->post_content), 'img');
											}
											// mufusli_elements_article
											break;
										// mufusli_elements_image
										case 'mufusli_elements_image':
											
											// Get End date
											$lEndDate = $lElement['mufusli_elements_image_end_date'];
											if ( $lEndDate && !empty($lEndDate ))
											{
												if ( $lEndDate < $lDateToday )
												{
													// continue ;
													break;
												}
											}
											// Get the fields for the image element
											$lImageId = $lElement['mufusli_elements_image_id'];
											$lImageTag = mm2_GetImageCode($lImageId, '270', true);
											
											$lImageFillType = $lElement['mufusli_elements_image_filltype'];
											$lImageClass = 'image';
											if ($lImageFillType == 'full')
												$lImageClass .= ' ' . $lImageFillType;
											
											$lImageTitle = $lElement['mufusli_elements_image_title'];
											$lImageInfoTitle = $lElement['mufusli_elements_image_infotitle'] ?? '';
											$lImageSubTitle = $lElement['mufusli_elements_image_subtitle'] ?? '';
											$lImageContent = '<p>' . $lElement['mufusli_elements_image_content'] . '</p>';
											$lImageUseExtendedContent = $lElement['mufusli_elements_image_useextendedcontent'];
											if ($lImageUseExtendedContent == true)
												$lImageContent = $lElement['mufusli_elements_image_content_extended'];
											
											$lInfoClass = 'info';
											$lImageContentAlignment = $lElement['mufusli_elements_image_alignment'];
											if (empty($lImageContentAlignment))
												$lImageContentAlignment = 'top';
											$lInfoClass .= ' ' . $lImageContentAlignment;
											
											
											$lImageUrl = $lElement['mufusli_elements_image_link'];
											$lImageTarget = $lElement['mufusli_elements_image_linktarget'];
											
											// Check if paid activation
											$lArticleIsPaidactivationBackgroundColor = $lBackgroundColor;
											$lArticleIsPaidactivationTextColor = '#FFFFFF';
											$lArticleIsPaidactivation = $lElement['mufusli_elements_image_paidactivation'] == '1';
											if ($lArticleIsPaidactivation) {
												$lElementCss = ' nohover paidactivation mufusli_' . $lMufusliCounter . '_' . $lElementsCount;
												$lArticleIsPaidactivationBackgroundColor = $lElement['mufusli_elements_image_paidactivation_backgroundcolor'] ?? $lArticleIsPaidactivationBackgroundColor;
												$lArticleIsPaidactivationTextColor = $lElement['mufusli_elements_image_paidactivation_textcolor'] ?? $lArticleIsPaidactivationTextColor;
												
												$lBackgroundColorRgba1 = hex2rgba($lArticleIsPaidactivationBackgroundColor, 0);
												$lBackgroundColorRgba2 = hex2rgba($lArticleIsPaidactivationBackgroundColor, 1);
											}
											?>
                                            <a href="<?php echo($lImageUrl); ?>" target="<?php echo($lImageTarget); ?>">
                                                <div class="<?php echo($lCellCss); ?>">
                                                    <div class="<?php echo($lImageClass . $lElementCss) ?>">
                                                        <div class="img">
															<?php echo($lImageTag); ?>
                                                        </div>
                                                        <div class="<?php echo($lInfoClass); ?>">
                                                            <span class="infotitle"><?php echo($lImageInfoTitle); ?></span>
                                                            <h3><?php echo($lImageTitle); ?></h3>
                                                            <span class="subtitle"><?php echo($lImageSubTitle); ?></span>
															<?php echo($lImageContent); ?>
                                                        </div>
                                                    </div>
													<?php
													if ($lArticleIsPaidactivation) {
														?>
                                                        <div class="paidactivation_hint" style="background-color: <?php echo($lArticleIsPaidactivationBackgroundColor); ?>">
                                                            <span style="color: <?php echo($lArticleIsPaidactivationTextColor); ?>">Entgeltliche Einschaltung</span>
                                                        </div>
                                                        <style>
                                                            .wrapper_mufusli .mufusli .carousel .mufusli_<?php echo($lMufusliCounter); ?>_<?php echo($lElementsCount); ?> .info::after {
                                                                background: linear-gradient(180deg, <?php echo($lBackgroundColorRgba1); ?> 0%, <?php echo($lBackgroundColorRgba2); ?> 100%);
                                                            }
                                                        </style>
														<?php
													}
													?>
                                                </div>
                                            </a>
											<?php
											break;
										//mufusli_elements_text
										case 'mufusli_elements_text':
											// Get End date
											$lEndDate = $lElement['mufusli_elements_text_end_date'];
											if ( $lEndDate && !empty($lEndDate ))
											{
												if ( $lEndDate < $lDateToday )
												{
													// continue ;
                                                    break;
												}
											}
											
											// Get the fields for the article element
											$lTextTitle = $lElement['mufusli_elements_text_title'];
											
											$lTextContent = '<p>' . $lElement['mufusli_elements_text_content'] . '</p>';
											if ($lElement['mufusli_elements_text_extendedcontent'] == true)
												$lTextContent = $lElement['mufusli_elements_text_content_extended'];
											
											// Check if paid activation
											$lArticleIsPaidactivationBackgroundColor = $lBackgroundColor;
											$lArticleIsPaidactivationTextColor = '#FFFFFF';
											$lArticleIsPaidactivation = $lElement['mufusli_elements_text_paidactivation'] == '1';
											if ($lArticleIsPaidactivation) {
												$lElementCss = ' nohover paidactivation mufusli_' . $lMufusliCounter . '_' . $lElementsCount;
												$lArticleIsPaidactivationBackgroundColor = $lElement['mufusli_elements_text_paidactivation_backgroundcolor'] ?? $lArticleIsPaidactivationBackgroundColor;
												$lArticleIsPaidactivationTextColor = $lElement['mufusli_elements_text_paidactivation_textcolor'] ?? $lArticleIsPaidactivationTextColor;
												
												$lBackgroundColorRgba1 = hex2rgba($lArticleIsPaidactivationBackgroundColor, 0);
												$lBackgroundColorRgba2 = hex2rgba($lArticleIsPaidactivationBackgroundColor, 1);
											}
												?>
                                            <a href="#">
                                                <div class="<?php echo($lCellCss); ?>">
                                                    <div class="text<?php echo($lElementCss); ?>">
                                                        <div class="info"<?php if ($lArticleIsPaidactivation)
															echo(' style="color: ' . $lArticleIsPaidactivationTextColor . '"'); ?>>
															<?php
															// in assets/functions//mm2_helpers.php
															echo mm2_helpers_headline($lTextTitle, 'h2');
															echo($lTextContent);
															?>
                                                        </div>
                                                    </div>
													<?php
													if ($lArticleIsPaidactivation) {
														?>
                                                        <div class="paidactivation_hint" style="background-color: <?php echo($lArticleIsPaidactivationBackgroundColor); ?>">
                                                            <span style="color: <?php echo($lArticleIsPaidactivationTextColor); ?>">Entgeltliche Einschaltung</span>
                                                        </div>
                                                        <style>
                                                            .wrapper_mufusli .mufusli .carousel .mufusli_<?php echo($lMufusliCounter); ?>_<?php echo($lElementsCount); ?> .info::after {
                                                                background: linear-gradient(180deg, <?php echo($lBackgroundColorRgba1); ?> 0%, <?php echo($lBackgroundColorRgba2); ?> 100%);
                                                            }
                                                        </style>
														<?php
													}
													?>
                                                </div>
                                            </a>
											<?php
				    						// mufusli_elements_text
											break;
										// mufusli_elements_vimeo
										case 'mufusli_elements_vimeo':
											
											// Get End date
											$lEndDate = $lElement['mufusli_elements_vimeo_end_date'];
											if ( $lEndDate && !empty($lEndDate ))
											{
												if ( $lEndDate < $lDateToday )
												{
													// continue;
													break;
												}
											}
											
											// Check if Vimeo cookie are allowed
											$lVimeoDnt = '';
											if (function_exists('BorlabsCookieHelper')) {
												if (!BorlabsCookieHelper()->gaveConsent('vimeo')) {
													$lVimeoDnt = 'dnt=1&';
												}
											}
											
											// Get the fields for the video element
											$lVimeoId = $lElement['mufusli_elements_vimeo_id'];
											$lVimeoTag = '<iframe src="https://player.vimeo.com/video/' . $lVimeoId . '?' . $lVimeoDnt . 'app_id=122963" width="100%" height="100%" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe><script src="https://player.vimeo.com/api/player.js"></script>';
											$lPreviewImageId = $lElement['mufusli_elements_vimeo_previewimage'];
											$lPreviewImageTag = mm2_GetImageCode($lPreviewImageId, '270', true);
											$lVideoLength = $lElement['mufusli_elements_vimeo_minutes'];
											$lVideoInfoTitle = $lElement['mufusli_elements_vimeo_infotitle'];
											if (empty($lVideoInfoTitle)) {
												if (!empty($lVideoLength) && ($lVideoLength > 0)) {
													$hours = floor($lVideoLength / 60);
													$minutes = ($lVideoLength % 60);
													$lVideoInfoTitle = sprintf('%02d:%02d min', $hours, $minutes);
												} else {
													$lVideoInfoTitle = '&bnsp;';
												}
											}
											$lVideoTitle = $lElement['mufusli_elements_vimeo_title'];
											$lVideoSubTitle = $lElement['mufusli_elements_vimeo_subtitle'];
											if (empty($lVideoSubTitle))
												$lVideoSubTitle = '&nbsp;';
											$lVideoContent = wpautop($lElement['mufusli_elements_vimeo_content']);
											$lVimeoUrl = "javascript:openDarkbox('darkbox_vimeo_" . $lVimeoId . "')";
											
											// Get darkbox link
											$lDarkboxLink = $lElement['mufusli_elements_vimeo_link'];
											
											// Check if url is in space of MM
											$lArticleId = url_to_postid($lDarkboxLink);
											
											// If MM post, check if it is only for logged in users
											$lVisibleForAll = true;
											if ($lArticleId) {
												$lVisibleForAll = get_field('mm2_visibility', $lArticleId) === false;
											}
											
											// Check if user is logged in
											if (is_user_logged_in())
												$lVisibleForAll = true;
											
											// Check if only visible for logged in
											if (!$lVisibleForAll) {
												$lVimeoTag = '<div class="withloginonly"></div>';
												$lVimeoTag .= '<style>.withloginonly::before {color: ' . $lBackgroundColor . ' !important;}</style>';
											}
											
											// Check if paid activation
											$lArticleIsPaidactivationBackgroundColor = $lBackgroundColor;
											$lArticleIsPaidactivationTextColor = '#FFFFFF';
											$lArticleIsPaidactivation = $lElement['mufusli_elements_vimeo_paidactivation'] == '1';
											if ($lArticleIsPaidactivation) {
												$lElementCss = ' nohover paidactivation mufusli_' . $lMufusliCounter . '_' . $lElementsCount;
												$lArticleIsPaidactivationBackgroundColor = $lElement['mufusli_elements_vimeo_paidactivation_backgroundcolor'] ?? $lArticleIsPaidactivationBackgroundColor;
												$lArticleIsPaidactivationTextColor = $lElement['mufusli_elements_vimeo_paidactivation_textcolor'] ?? $lArticleIsPaidactivationTextColor;
												
												$lBackgroundColorRgba1 = hex2rgba($lArticleIsPaidactivationBackgroundColor, 0);
												$lBackgroundColorRgba2 = hex2rgba($lArticleIsPaidactivationBackgroundColor, 1);
											}
											?>
                                            <a href="<?php echo($lVimeoUrl); ?>">
                                                <div class="<?php echo($lCellCss); ?>">
                                                    <div class="video<?php echo($lElementCss); ?>">
                                                        <div class="img">
															<?php echo($lPreviewImageTag); ?>
                                                            <div class="article_icon"><i class="fas fa-video" aria-hidden="true"></i></div>
                                                        </div>
                                                        <div class="info"<?php if ($lArticleIsPaidactivation)
															echo(' style="color: ' . $lArticleIsPaidactivationTextColor . '"'); ?>>
                                                            <span class="infotitle"><?php echo($lVideoInfoTitle); ?></span>
                                                            <h3><?php echo($lVideoTitle) ?></h3>
                                                            <span class="subtitle"><?php echo($lVideoSubTitle); ?></span>
                                                            <p>
																<?php echo($lVideoContent) ?>
                                                            </p>
                                                        </div>
														<?php
														if ($lArticleIsPaidactivation) {
															?>
                                                            <div class="paidactivation_hint" style="background-color: <?php echo($lArticleIsPaidactivationBackgroundColor); ?>">
                                                                <span style="color: <?php echo($lArticleIsPaidactivationTextColor); ?>">Entgeltliche Einschaltung</span>
                                                            </div>
                                                            <style>
                                                                .wrapper_mufusli .mufusli .carousel .mufusli_<?php echo($lMufusliCounter); ?>_<?php echo($lElementsCount); ?> .info::after {
                                                                    background: linear-gradient(180deg, <?php echo($lBackgroundColorRgba1); ?> 0%, <?php echo($lBackgroundColorRgba2); ?> 100%);
                                                                }
                                                            </style>
															<?php
														}
														?>
                                                    </div>
                                                </div>
                                            </a>
											<?php
											// Append link if there to content for darkbox
											$lDarkBoxLinkContent = '';
											if (!empty($lDarkboxLink)) {
												if ($lVisibleForAll) {
													$lDarkboxLinkText = $lElement['mufusli_elements_vimeo_linktext'];
													$lDarkBoxLinkContent = '<p><a href="' . $lDarkboxLink . '" target="_self">' . $lDarkboxLinkText . '</a></p>';
												} else {
													$lDarkBoxLinkContent = '<p>Melden Sie sich bitte <a href="https://www.medmedia.at/community/registrieren/"><span>hier</span></a> ';
													$lDarkBoxLinkContent .= 'kostenlos und unverbindlich an, um den Inhalt vollständig einzusehen und weitere Services von';
													$lDarkBoxLinkContent .= 'www.medmedia.at zu nutzen.</p>';
													$lDarkBoxLinkContent .= '<p><a href="https://www.medmedia.at/community/registrieren/" id="reg_link">Zur Anmeldung</a></p>';
												}
											}
											
											mm2_DarkboxPrint('vimeo_' . $lVimeoId, $lVimeoTag, $lVideoTitle, $lVideoContent . $lDarkBoxLinkContent, 'vimeo');
											// mufusli_elements_vimeo
											break;
										
										default:
											break;
									} // endswitch
									
									
								} // endforeach
								
								// Is there a detail button
								$lShowDetailsButton = $aOptions['mufusli_detailbutton'] == '1';
								?>
                                <div class="navigation mufusli_<?php echo($lMufusliCounter); ?>">
                                    <ul>
                                        <li class="left"><a href="javascript:showPrevMufusliPage(<?php echo($lMufusliCounter); ?>);"><i class="far fa-chevron-left"></i></a></li>
                                        <li class="right"><a href="javascript:showNextMufusliPage(<?php echo($lMufusliCounter); ?>);"><i class="far fa-chevron-right"></i></a></li>
                                    </ul>
                                </div>

                                <div class="dots mufusli_<?php echo($lMufusliCounter); ?>">
									<?php
									if ($lShowDetailsButton) {
										$lDetailButtonText = $aOptions['mufusli_detailbutton_text'] . ' &raquo;';
										$lDetailButtonLink = $aOptions['mufusli_detailbutton_link'];
										$lDetailButtonTarget = $aOptions['mufusli_detailbutton_linktarget'];
										if (!$lDetailButtonTarget)
											$lDetailButtonTarget = '_self';
										?>
                                        <p class="more">
                                            <a href="<?php echo($lDetailButtonLink); ?>" target="<?php echo($lDetailButtonTarget); ?>"><?php echo($lDetailButtonText); ?></a>
                                        </p>
										<?php
									}
									
									?>
                                    <ul>
                                        <li class="current"><a href="javascript:showMufusliPage(<?php echo($lMufusliCounter . ', 0'); ?>);"></a>
                                        </li>
										<?php
										// Show more dots if necessary
										for ($i = 1; $i < $lElementsCount; $i++) {
											?>
                                            <li>
                                                <a href="javascript:showMufusliPage(<?php echo($lMufusliCounter . ', ' . $i); ?>);"></a>
                                            </li>
											<?php
										}
										?>
                                    </ul>
                                </div>
								<?php
								if ($lShowDetailsButton) {
									?>
                                    <div id="clear mufusli_<?php echo($lMufusliCounter); ?>" style="clear: both">&nbsp;</div>
									<?php
								}
								?>
                            </div>
                        </div>
                    </div>
					<?php
				}
			}
            else
            {
				// Content is hidden be security
				?>
                <div class="mufusli" mufusliid="<?php echo($lMufusliCounter); ?>"<?php echo($lLayoutTag); ?>>
                    <div class="grid-container">
						<?php echo($lLoginText); ?>
                    </div>
                </div>
				<?php
			}
			?>
            <div style="clear: both;"></div>
        </div>
		<?php
      //  $lArticleId,$aOptions, $aElements ,14063541
        $lArticleId = $lElement['mufusli_elements_article_id'];
       // $lArticle = get_post($lArticleId);
    //    var_dump($lArticleId);
        $selectedValue = get_field('field_650164606d6fd','14063541');
        if ($selectedValue) {
            var_dump($selectedValue) ;
        } else {
            echo 'No value selected.';
        }

	}
}

/**
 * @param $aId
 * @param $aImageOrVideoTag
 * @param $aTitle
 * @param $aContent
 * @param $aTypeClass
 * @return void
 */
function mm2_DarkboxPrint($aId, $aImageOrVideoTag, $aTitle, $aContent, $aTypeClass = 'img') {
	?>
    <div class="darkbox" id="darkbox_<?php echo($aId); ?>">
        <div class="db_content">
            <div class="<?php echo($aTypeClass) ?>">
				<?php echo($aImageOrVideoTag); ?>
            </div>
        </div>
        <div class="db_info">
            <div class="db_close">
                <a href="javascript:closeDarkbox()">
                    Schließen &nbsp; <i class="fal fa-times"></i>
                </a>
            </div>
            <h3 class="db_title"><?php echo($aTitle); ?></h3>
            <div>
				<?php echo($aContent); ?>
            </div>
        </div>
    </div>
	<?php
} // mm2_DarkboxPrint


/**
 * @desc Get Mufusli Data from Repeater
 * @param array $lTeaserFieldsData
 * @param int $i
 * @return array|void
 */
function GetMufusliData( array $lTeaserFieldsData, int $i )
{
	global $_ldebug;
	if ( $_ldebug )
	{
		// echo ('<pre>Counter in Mufusli' . $i);
		// var_dump($lTeaserFields['mm2_imfokus_categories_' . $i . '_mufusli_elements']);
		// echo('</pre>');
	}
	// Create the new mufusli elements
	$lMufusliElements = array();
	$lCategoryElements = array();
	// Are there mufusli elements in this category
	if (
		empty($lTeaserFieldsData['mm2_imfokus_categories_' . $i . '_mufusli_elements']) &&
		empty($lTeaserFieldsData['mm2_imfokus_categories_' . $i . '_mufusli_infobox_content'])
	)
	{
		return $lCategoryElements;
	}

	$lCategoryType = 'mufusli';
	// Copy the values
	$lPrefix = 'mm2_imfokus_categories_' . $i . '_';
        $lOptions['mufusli_infobox'] = $lTeaserFieldsData[$lPrefix . 'mufusli_infobox'];
        $lOptions['mufusli_infobox_content'] = $lTeaserFieldsData[$lPrefix . 'mufusli_infobox_content'];
        $lOptions['mufusli_title'] = $lTeaserFieldsData[$lPrefix . 'mufusli_title'];
        $lOptions['mufusli_backgroundcolor'] = $lTeaserFieldsData[$lPrefix . 'mufusli_backgroundcolor'];
        $lOptions['mufusli_layout'] = $lTeaserFieldsData[$lPrefix . 'mufusli_layout'];
        $lOptions['mufusli_css'] = $lTeaserFieldsData[$lPrefix . 'mufusli_css'];
        $lOptions['mufusli_detailbutton'] = $lTeaserFieldsData[$lPrefix . 'mufusli_detailbutton'];
        $lOptions['mufusli_detailbutton_text'] = $lTeaserFieldsData[$lPrefix . 'mufusli_detailbutton_text'];
        $lOptions['mufusli_detailbutton_link'] = $lTeaserFieldsData[$lPrefix . 'mufusli_detailbutton_link'];
        $lOptions['mufusli_detailbutton_linktarget'] = $lTeaserFieldsData[$lPrefix . 'mufusli_detailbutton_linktarget'];
        $lOptions['mufusli_visibility_mode'] = $lTeaserFieldsData[$lPrefix . 'mufusli_visibility_mode'];
        $lOptions['mufusli_visibility_grouprights'] = $lTeaserFieldsData[$lPrefix . 'mufusli_visibility_grouprights'];
        $lOptions['mufusli_visibility_logintext'] = $lTeaserFieldsData[$lPrefix . 'mufusli_visibility_logintext'];

	// Get the elements
	$lElementTypes = unserialize($lTeaserFieldsData[$lPrefix . 'mufusli_elements']);
	foreach ($lElementTypes as $lKey => $lElementType)
	{
		$lMufusliElement = array();
		$lMufusliElement['acf_fc_layout'] = $lElementType;
		foreach ($lTeaserFieldsData as $lTeaserKey => $lTeaserValue)
		{
			$lTeaserfieldKey = $lPrefix . 'mufusli_elements_' . $lKey . '_' . $lElementType . '_';
			if (strpos($lTeaserKey, $lTeaserfieldKey) === 0)
			{
				$lMufusliElementKey = $lElementType . '_' . substr($lTeaserKey, strlen($lTeaserfieldKey));
				$lMufusliElement[$lMufusliElementKey] = $lTeaserValue;
			}
		}
		// Add it to the mufusli list
		$lMufusliElements[] = $lMufusliElement;
	}

	// Add it to the list
	$lCategoryElements[] = array(
		'type' => $lCategoryType,
		'index' => $i,
		//'anchor' => $_lcat->slug,
		'options' => $lOptions,
		'elements' => $lMufusliElements,
		//'title' => $_lcat->name
	);
	return $lCategoryElements;

} // GetMufusliData


/**
 * @param string $ChannelCpt
 */
function PrintMufusliFromOptions( string $ChannelCpt  )
{
    if ( empty($ChannelCpt ))
	{
        return false;
    }

	// Get the mufusli elements
	$lElements = get_field($ChannelCpt.'_mufusli', 'option');
    //
	// Are there mufusli elements in this category
	if (
		empty($lElements['mufusli_elements']) &&
		empty($lElements['mufusli_infobox_content'])
	)
	{
		return false;
	}

	$lOptions['mufusli_infobox'] = $lElements['mufusli_infobox'];
	$lOptions['mufusli_infobox_content'] = $lElements['mufusli_infobox_content'];
	$lOptions['mufusli_title'] = $lElements['mufusli_title'];
	$lOptions['mufusli_backgroundcolor'] = $lElements['mufusli_backgroundcolor'];
	$lOptions['mufusli_layout'] = $lElements['mufusli_layout'];
	$lOptions['mufusli_css'] = $lElements['mufusli_css'];
	$lOptions['mufusli_detailbutton'] = $lElements['mufusli_detailbutton'];
	$lOptions['mufusli_detailbutton_text'] = $lElements['mufusli_detailbutton_text'];
	$lOptions['mufusli_detailbutton_link'] = $lElements['mufusli_detailbutton_link'];
	$lOptions['mufusli_detailbutton_linktarget'] = $lElements['mufusli_detailbutton_linktarget'];
	$lOptions['mufusli_visibility_mode'] = $lElements['mufusli_visibility_mode'];
	$lOptions['mufusli_visibility_grouprights'] = $lElements['mufusli_visibility_grouprights'];
	$lOptions['mufusli_visibility_logintext'] = $lElements['mufusli_visibility_logintext'];

	// Get the elements
	$lMufusliElements = $lElements['mufusli_elements'];
    // Print the MuFuSli
     mm2_MuFuSliPrint($lOptions, $lMufusliElements);
} // PrintMufusliFromOptions

/**
 * @param string $ChannelCpt
 * @return false|void
 */
function PrintMufusliRepeaterFromOptions( string $ChannelCpt )
{
	if ( empty($ChannelCpt ))
	{
		return false;
	}

	// Get the mufusli elements
	$lElements = get_field( $ChannelCpt.'_mm2_mufusli-repeater', 'option' ) ?? '';
	if ( !empty($lElements) )
	{
	    foreach ( $lElements as $key=>$lElement )
	    {
		    if (
			    empty($lElement['mufusli_elements']) &&
			    empty($lElement['mufusli_infobox_content'])
		    )
		    {
			    continue;
		    }
		    $lOptions = array();
		    $lOptions['mufusli_infobox'] = $lElement['mufusli_infobox'];
		    $lOptions['mufusli_infobox_content'] = $lElement['mufusli_infobox_content'];
		    $lOptions['mufusli_title'] = $lElement['mufusli_title'];
		    $lOptions['mufusli_backgroundcolor'] = $lElement['mufusli_backgroundcolor'];
		    $lOptions['mufusli_layout'] = $lElement['mufusli_layout'];
		    $lOptions['mufusli_css'] = $lElement['mufusli_css'];
		    $lOptions['mufusli_detailbutton'] = $lElement['mufusli_detailbutton'];
		    $lOptions['mufusli_detailbutton_text'] = $lElement['mufusli_detailbutton_text'];
		    $lOptions['mufusli_detailbutton_link'] = $lElement['mufusli_detailbutton_link'];
		    $lOptions['mufusli_detailbutton_linktarget'] = $lElement['mufusli_detailbutton_linktarget'];
		    $lOptions['mufusli_visibility_mode'] = $lElement['mufusli_visibility_mode'];
		    $lOptions['mufusli_visibility_grouprights'] = $lElement['mufusli_visibility_grouprights'];
		    $lOptions['mufusli_visibility_logintext'] = $lElement['mufusli_visibility_logintext'];

            $lOptions['mufusli_elements_taxonomy'] = $lElement['mufusli_elements_taxonomy'];

            // Get the elements
		    $lMufusliElements  =  $lElement['mufusli_elements'];
		    // Print the MuFuSli
		    mm2_MuFuSliPrint($lOptions, $lMufusliElements);
	    }
	}
} // PrintMufusliRepeaterFromOptio


    ?>

