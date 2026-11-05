<?php
/**
 * Photo approval (contract gating rule 2).
 *
 * @package hcwa-core
 */

$approved     = array( 'approved_for_web' => true, 'identifiable_child' => false );
$approved_kid = array( 'approved_for_web' => true, 'identifiable_child' => true );
$unapproved   = array( 'approved_for_web' => false, 'identifiable_child' => false );

hcwa_assert_true( hcwa_image_decision( $approved, false ), 'approved photo shows' );
hcwa_assert_true( hcwa_image_decision( $approved, true ), 'approved, not identifiable: shows on Little Rose' );
hcwa_assert_true( hcwa_image_decision( $approved_kid, false ), 'identifiable child shows where allowed' );
hcwa_assert_false( hcwa_image_decision( $approved_kid, true ), 'identifiable child refused on Little Rose' );
hcwa_assert_false( hcwa_image_decision( $unapproved, false ), 'unapproved photo refused' );
hcwa_assert_false( hcwa_image_decision( $unapproved, true ), 'unapproved photo refused on Little Rose' );
hcwa_assert_false( hcwa_image_decision( array(), false ), 'missing flags count as not approved' );

// Contexts.
hcwa_assert_true( hcwa_image_context_no_identifiable( true ), 'bool context' );
hcwa_assert_true( hcwa_image_context_no_identifiable( array( 'no_identifiable_children' => true ) ), 'array context' );
hcwa_assert_false( hcwa_image_context_no_identifiable( array() ), 'empty array context' );

hcwa_test_post( array( 'ID' => 10, 'post_type' => 'hcwa_center' ), array( 'hcwa_no_identifiable_children' => '1' ) );
hcwa_test_post( array( 'ID' => 11, 'post_type' => 'hcwa_center' ), array( 'hcwa_no_identifiable_children' => '' ) );
hcwa_test_post( array( 'ID' => 20, 'post_type' => 'hcwa_program' ), array( 'hcwa_center_id' => 10 ) );
hcwa_test_post( array( 'ID' => 21, 'post_type' => 'hcwa_program' ), array( 'hcwa_center_id' => 11 ) );

hcwa_assert_true( hcwa_image_context_no_identifiable( 10 ), 'Little Rose center' );
hcwa_assert_false( hcwa_image_context_no_identifiable( 11 ), 'other center' );
hcwa_assert_true( hcwa_image_context_no_identifiable( 20 ), 'program run by Little Rose inherits the rule' );
hcwa_assert_false( hcwa_image_context_no_identifiable( 21 ), 'program of another center' );
hcwa_assert_false( hcwa_image_context_no_identifiable( 999 ), 'unknown post' );

// hcwa_image_allowed with stored flags.
hcwa_test_post( array( 'ID' => 100, 'post_type' => 'attachment', 'post_status' => 'inherit' ), array( 'hcwa_approved_for_web' => '1', 'hcwa_identifiable_child' => '1' ) );
hcwa_test_post( array( 'ID' => 101, 'post_type' => 'attachment', 'post_status' => 'inherit' ), array( 'hcwa_approved_for_web' => '' ) );
hcwa_test_post( array( 'ID' => 102, 'post_type' => 'attachment', 'post_status' => 'inherit' ), array( 'hcwa_approved_for_web' => '1' ) );

hcwa_assert_true( hcwa_image_allowed( 100, 11 ), 'approved identifiable photo on another center' );
hcwa_assert_false( hcwa_image_allowed( 100, 10 ), 'approved identifiable photo refused on Little Rose' );
hcwa_assert_false( hcwa_image_allowed( 100, 20 ), 'and on a Little Rose program' );
hcwa_assert_true( hcwa_image_allowed( 102, 10 ), 'approved non-identifiable photo on Little Rose' );
hcwa_assert_false( hcwa_image_allowed( 101, 11 ), 'unapproved photo refused' );
hcwa_assert_false( hcwa_image_allowed( 999, 11 ), 'missing attachment refused' );
hcwa_assert_false( hcwa_image_allowed( 0 ), 'ID 0 refused' );

$log = get_option( 'hcwa_image_refusals', array() );
hcwa_assert_true( isset( $log['101:not_approved'], $log['100:identifiable_child'] ), 'refusals logged for the Media library' );
hcwa_assert_same( '', hcwa_image( 101, 'large', array(), 11 ), 'refused image prints nothing' );

// Partner logos (rule 5).
hcwa_test_post( array( 'ID' => 300, 'post_type' => 'hcwa_partner' ), array( 'hcwa_logo_permission' => '1' ) );
hcwa_test_post( array( 'ID' => 301, 'post_type' => 'hcwa_partner' ), array() );
hcwa_assert_true( hcwa_partner_logo_allowed( 300, true ), 'logo with permission on production' );
hcwa_assert_false( hcwa_partner_logo_allowed( 301, true ), 'logo without permission hidden on production' );
hcwa_assert_true( hcwa_partner_logo_allowed( 301, false ), 'staging shows every logo' );
