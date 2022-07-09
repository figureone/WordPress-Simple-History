<?php
use \Step\Acceptance\Admin;
/**
 * 'privacy_data_export_requested'         => _x( 'Requested a privacy data export for user "{user_email}"', 'Logger: Privacy', 'simple-history' ),
 * 'privacy_data_export_admin_downloaded'  => _x( 'Downloaded personal data export file for user "{user_email}"', 'Logger: Privacy', 'simple-history' ),
 * 'privacy_data_export_emailed'           => _x( 'Sent email with personal data export download info for user "{user_email}"', 'Logger: Privacy', 'simple-history' ),
 * 'privacy_data_export_request_confirmed' => _x( 'Confirmed data export request for "{user_email}"', 'Logger: Privacy', 'simple-history' ),
 * 'privacy_data_export_removed'           => _x( 'Removed data export request for "{user_email}"', 'Logger: Privacy', 'simple-history' ),
 * 'data_erasure_request_sent'             => _x( 'Sent data erasure request for "{user_email}"', 'Logger: Privacy', 'simple-history' ),
 * 'data_erasure_request_confirmed'        => _x( 'Confirmed data erasure request for "{user_email}"', 'Logger: Privacy', 'simple-history' ),
 * 'data_erasure_request_handled'          => _x( 'Erased personal data for "{user_email}"', 'Logger: Privacy', 'simple-history' ),
 * 'data_erasure_request_removed'          => _x( 'Removed personal data removal request for "{user_email}"', 'Logger: Privacy', 'simple-history' ),
 */

class SimplePrivacyLoggerCest
{
    /**
     * Go to privacy page and create a new privacy page.
     *
     * privacy_page_created
     */
    public function logPrivacyPageCreated(Admin $I)
    {
        $I->loginAsAdmin();
        $I->amOnAdminPage('options-privacy.php');

        $I->click('Create');

        $I->seeLogInitiator('wp_user');
        $I->seeLogMessage('Created a new privacy page "Privacy Policy"', 0);
        $I->seeLogContext([
            'new_post_title' => 'Privacy Policy',
            'prev_post_id' => 0,
            'new_post_id' => 2,
        ]);
    }

    /**
     * Go to privacy page and select a new privacy page.
     *
     * privacy_page_set
     */
    public function logPrivacyPageSet(Admin $I)
    {
        $I->havePageInDatabase([
            'post_title' => 'My new privacy page',
        ]);

        $I->loginAsAdmin();        
        $I->amOnAdminPage('options-privacy.php');

        $I->selectOption('#page_for_privacy_policy', 'My new privacy page');
        $I->click('Use This Page');

        $I->seeLogInitiator('wp_user');
        $I->seeLogMessage('Set privacy page to page "My new privacy page"');
    }

    /**
     * Go to export personal data page and add data export request.
     *
     * Message key: privacy_data_export_requested
     */
    public function logDataExportRequest(Admin $I)
    {
        $I->haveUserInDatabase('myNewUser');
        
        $I->loginAsAdmin();        
        $I->amOnAdminPage('export-personal-data.php');
        $I->fillField('#username_or_email_for_privacy_request', 'myNewUser');
        $I->click('Send Request');

        $I->seeLogInitiator('wp_user');
        $I->seeLogMessage('Requested a privacy data export for user "myNewUser@example.com"');
        $I->seeLogContext([
            'send_confirmation_email' => 1,
        ]);
    }
}