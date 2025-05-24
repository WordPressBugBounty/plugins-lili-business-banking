<?php
/**
 * Enqueue scripts and styles on the Lili admin page.
 */
function lili_display_user_details($user_data = null, $transactions_data = null)
{

    ?>
    <div class="lili-banking-wrapper">

        <div class="lili_plugin">

            <div class="lili_plugin_container with-border">

                <?php lili_display_lili_header(true); ?>

                <div class="lili_dashboard">

                    <div class="lili_dashboard__balances">

                        <div class="checking_balance balance-box">

                            <div class="heading">

                                <div class="info">
                                    <label><?php esc_html_e('Checking Account Balance', 'lili'); ?></label>
                                    <div class="number">
                                        <div class="currency">$</div>
                                        <span><?php echo esc_html(number_format((float)$user_data->accounts[0]->currentBalance, 2)); ?></span>
                                    </div>
                                </div>

                                <div class="thumb">

                                    <img src="<?php echo esc_url(LILI_PLUGIN_URL); ?>assets/images/balance.svg"
                                         alt="Balance">

                                </div>

                            </div>

                        </div>

                        <div class="savings_balance balance-box">

                            <div class="heading">

                                <div class="info">
                                    <label><?php esc_html_e('Savings Account Balance', 'lili'); ?></label>
                                    <div class="number">
                                        <div class="currency">$</div>
                                        <span><?php echo esc_html(number_format((float)$user_data->accounts[0]->availableBalance, 2)); ?></span>
                                    </div>
                                </div>

                                <div class="thumb">
                                    <img src="<?php echo esc_url(LILI_PLUGIN_URL); ?>assets/images/saving.svg"
                                         alt="Savings">
                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="lili_dashboard__transactions">

                        <div class="title">
                            <?php esc_html_e('Latest Transactions', 'lili'); ?>
                        </div>

                        <?php if (isset($transactions_data->transactions)): ?>
                            <table class="transaction-table">
                                <thead>
                                <tr>
                                    <th><?php esc_html_e('Date', 'lili'); ?></th>
                                    <th><?php esc_html_e('Description', 'lili'); ?></th>
                                    <th><?php esc_html_e('Amount', 'lili'); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $transactions = array_slice($transactions_data->transactions, 0, 5);
                                foreach ($transactions as $transaction):
                                    $date = new DateTime($transaction->settledAt);
                                    $amount = number_format(abs((float)$transaction->amount), 2);
                                    $is_negative = (float)$transaction->amount < 0;
                                    ?>
                                    <tr>
                                        <td class="date"><?php echo esc_html($date->format('M j, Y')); ?></td>
                                        <td><?php echo esc_html($transaction->description); ?></td>
                                        <td class="amount <?php echo $is_negative ? 'negative' : 'positive'; ?>">
                                            <?php echo $is_negative ? '-' : ''; ?>$<?php echo esc_html($amount); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>
    </div>
    <?php
}

/**
 * Display the Lili header.
 *
 * @param $with_dashboard_button
 * @return void
 */
function lili_display_lili_header($with_dashboard_button = false)
{

    ?>
    <div class="lili_plugin_header <?php if ($with_dashboard_button) { ?>inside_container<?php } ?>">
        <a href="https://lili.co" target="_blank">
            <img src="<?php echo esc_url(LILI_PLUGIN_URL); ?>assets/images/logo_business_banking.svg" alt="Lili Logo">
        </a>

        <?php if ($with_dashboard_button): ?>
            <div class="lili_plugin_header_buttons">
                <form method="post" action="">
                    <?php wp_nonce_field('lili_logout_action', 'lili_logout_nonce'); ?>
                    <input type="submit" name="lili_logout_submit" id="lili_logout_submit"
                           class="button button-small button-secondary"
                           value="Logout">
                </form>
                <a href="https://app.lili.co" target="_blank" class="button button-primary button-hero">Go to Lili
                    Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
    <?php

}

/**
 * Display the Lili login form.
 *
 * @return void
 */
function lili_display_disclaimer_text()
{

    ?>
    <div class="lili_disclaimer">
        <ul>
            <li>
                The Annual Percentage Yield (“APY”) for the Lili Savings Account is variable and may change at any time.
                The disclosed APY is effective as of January 1, 2025. Must have at least $0.01 in savings to earn
                interest. The APY applies to balances of up to and including $1,000,000. Any portions of a balance over
                $1,000,000 will not earn interest or have a yield. Available to Lili Pro, Lili Smart, and Lili Premium
                account holders only; applicable monthly account fees apply.
            </li>
            <li>
                Accounting, tax preparation and invoicing software is available to Lili Smart and Lili Premium account
                holders only; applicable monthly account fees apply. For details, please refer to your Sunrise Banks
                Account Agreement.
            </li>
        </ul>
    </div>
    <?php


}

/**
 * Display the Lili login form.
 *
 * @return void
 */
function lili_display_login_form()
{
    ?>
    <div class="lili_plugin">
        <?php lili_display_lili_header(); ?>

        <div class="lili_plugin_container">

            <div class="lili_plugin_tabs">

                <div class="lili_plugin_tabs__content">

                    <div class="heading">
                        <button class="tab-button active"
                                data-tab="new-customer"><?php esc_html_e('New to Lili?', 'lili'); ?></button>
                        <button class="tab-button"
                                data-tab="existing-customer"><?php esc_html_e('Already have an account?', 'lili'); ?></button>
                    </div>

                    <div class="content_tabs content-wrap">

                        <div class="tab-content active" id="new-customer-content">

                            <div class="customer_content">

                                <div class="customer_content__heading">
                                    <?php esc_html_e('Lili Business Banking Platform for eCommerce Merchants', 'lili'); ?>
                                </div>

                                <div class="customer_content__subtitle">
                                    <?php esc_html_e('Seamless banking, accounting, and taxes - all in one.', 'lili'); ?>
                                </div>

                                <div class="customer_content__benefits">
                                    <ul>
                                        <li>
                                            <strong>Advanced business checking account</strong>
                                            <br>
                                            Lili Visa® Debit Card, express ACH, domestic & international wire transfers,
                                            with no hidden fees, no overdraft fees, and no minimum balance required.
                                        </li>
                                        <li>Savings account with 3.00% APY<sup>1</sup></li>
                                        <li>Built-in accounting software<sup>2</sup> including bill pay, invoicing,
                                            automated transaction categorization and reports
                                        </li>
                                        <li>Automate tax savings throughout the year and receive auto-generated expense
                                            reports and pre-filled tax forms
                                        </li>
                                    </ul>
                                </div>

                                <div class="customer_content__cta">
                                    <a href="https://lp.lili.co/lili-for-ecommerce/?utm_medium=partners&utm_source=wprepo&utm_campaign=plugin"
                                       target="_blank" class="button button-primary button-hero">Open Account for
                                        Free</a>
                                </div>

                            </div>

                        </div>

                        <div class="tab-content content-wrap" id="existing-customer-content">
                            <div class="lili_login_form">

                                <div class="lili_login_form__title">
                                    <?php esc_html_e('Welcome Back', 'lili'); ?>
                                </div>

                                <form method="post" action="" id="lili-login-form">
                                    <div class="form_wrap login">
                                        <div class="form_wrap__group">
                                            <label class="small"><?php esc_html_e('EMAIL ADDRESS', 'lili'); ?></label>
                                            <input type="text" name="lili_username" id="lili_username"
                                                   class="regular-text"
                                                   required>
                                        </div>

                                        <div class="form_wrap__group">
                                            <label class="small"><?php esc_html_e('PASSWORD', 'lili'); ?></label>
                                            <input type="password" name="lili_password" id="lili_password"
                                                   class="regular-text"
                                                   required>
                                        </div>

                                        <div class="form_wrap__group submit-wrap">
                                            <input type="submit" name="lili_login_submit" id="lili_login_submit"
                                                   class="button button-primary button-hero" value="Log In">
                                        </div>
                                    </div>
                                </form>

                            </div>
                        </div>

                    </div>

                    <div class="badges">
                        <img src="<?php echo esc_url(LILI_PLUGIN_URL); ?>assets/images/badges.svg" alt="Badges">
                    </div>

                    <div class="disclaimer_text_wrap">
                        <?php esc_html_e('Lili is a financial technology company, not a bank. Banking services provided by Sunrise Banks, N.A., Member FDIC.', 'lili'); ?>
                    </div>

                </div>

                <div class="lili_plugin_tabs__thumb">
                    <img src="<?php echo esc_url(LILI_PLUGIN_URL); ?>assets/images/thumb.svg" alt="Lili">
                </div>

            </div>

        </div>

        <?php lili_display_disclaimer_text(); ?>

    </div>

    <?php
}