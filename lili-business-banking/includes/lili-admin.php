<?php

/**
 * Enqueue scripts and styles on the Lili admin page.
 *
 * @param string $hook Hook suffix for the current admin page.
 */
function lili_admin_enqueue_scripts($hook)
{
    // Only include on our plugin page
    if ($hook != 'toplevel_page_lili-integration') {
        return;
    }

    wp_enqueue_style(
        'lili-inter-font',
        LILI_PLUGIN_URL . 'assets/fonts/inter/stylesheet.css',
        array(),
        '1.0.0'
    );

    wp_enqueue_style(
        'lili-admin-style',
        LILI_PLUGIN_URL . 'assets/css/main.css',
        array(),
        '1.0.0'
    );

    wp_enqueue_script('lili-login', LILI_PLUGIN_URL . 'assets/js/main.js', array('jquery'), '1.0.0', true);
    wp_localize_script('lili-login', 'liliAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'send_otp_nonce' => wp_create_nonce('lili_send_otp_nonce'),
        'login_nonce' => wp_create_nonce('lili_login_nonce'),
        'validate_otp_nonce' => wp_create_nonce('lili_validate_otp_nonce')
    ));

}

add_action('admin_enqueue_scripts', 'lili_admin_enqueue_scripts');

/**
 * Render the Lili admin page.
 */
function lili_admin_menu()
{
    add_menu_page(
        'Business Banking',
        'Business Banking',
        'manage_options',
        'lili-integration',
        'lili_admin_page',
        plugin_dir_url(__FILE__) . '../assets/images/favicon.svg'
    );
}

add_action('admin_menu', 'lili_admin_menu');

// Add to top admin bar
function lili_admin_bar_menu($wp_admin_bar)
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $base64_svg = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAzMiAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGcgaWQ9ImxvZ28gMiIgY2xpcC1wYXRoPSJ1cmwoI2NsaXAwXzY0NTNfMzA4KSI+CjxwYXRoIGlkPSJWZWN0b3IiIGZpbGwtcnVsZT0iZXZlbm9kZCIgY2xpcC1ydWxlPSJldmVub2RkIiBkPSJNNi41MzQ2IDExLjk4OTNDNi41MzgzNSAxMi4wOTY5IDYuNTgwMDUgMTIuMTcyOCA2LjY5NTY0IDEyLjE2NTJDNi44MzE4MSAxMi4xNTU2IDcuMDIwNTkgMTEuOTAwNiA3LjA5NjgyIDExLjgwOTNDNy4yNDY1OSAxMS42MzA0IDcuMzg1OTkgMTEuNDQyMyA3LjUzMTY0IDExLjI2MDJDOC41MzEzOCAxMC4wMTA2IDkuNTEzMjEgOC43NTIxNiAxMC4zMzAxIDcuMzcxNjVDMTAuNzgxNyA2LjYwNzk0IDExLjI3NTggNS43MDA1NCAxMS42MzEzIDQuODkwM0MxMS45NzkgNC4wOTkwMyAxMi4zMzc5IDMuMzc1NzYgMTIuMzUzOSAyLjQ0MjZDMTIuMzU3MyAyLjI1Mjc0IDEyLjI5NTIgMi4wNjQ2OCAxMi4xMTE4IDIuMDY0NjhDMTIuMDI4MiAyLjA2NDY4IDExLjkyODcgMi4xMDUzIDExLjgyMTIgMi4xODk0QzExLjUzNzIgMi40MTE0NiAxMS4yODY5IDIuNzAyNzcgMTEuMDYyOCAyLjk4MzE3QzEwLjU2NjQgMy42MDI4NCAxMC4xMzc5IDQuMjkwMTQgOS42OTM0IDQuOTQ3MkM4Ljc3MzY2IDYuMzA2OTYgOC4wODkwNCA3Ljc3MzAxIDcuNDk2NTcgOS4yOTYzMUM3LjIyNjAyIDkuOTkyNTYgNi45MDg0IDEwLjY0MjEgNi43MDM2OSAxMS4zNTY2QzYuNjAzODUgMTEuNjg2MiA2LjUyODE1IDExLjgwNTggNi41MzQ2IDExLjk4OTNaTTE5LjI5ODQgMTEuOTg5M0MxOS4zMDI0IDEyLjA5NjkgMTkuMzQzOSAxMi4xNzI4IDE5LjQ1OTUgMTIuMTY1MkMxOS41OTU1IDEyLjE1NTYgMTkuNzg0MyAxMS45MDA2IDE5Ljg2MDcgMTEuODA5M0MyMC4wMTA0IDExLjYzMDQgMjAuMTQ5OCAxMS40NDIzIDIwLjI5NTUgMTEuMjYwMkMyMS4yOTUgMTAuMDEwNiAyMi4yNzcxIDguNzUyMTYgMjMuMDkzOSA3LjM3MTY1QzIzLjU0NTYgNi42MDc5NCAyNC4wMzk2IDUuNzAwNTQgMjQuMzk1MiA0Ljg5MDNDMjQuNzQyOCA0LjA5OTAzIDI1LjEwMTggMy4zNzU3NiAyNS4xMTc5IDIuNDQyNkMyNS4xMjExIDIuMjUyNzQgMjUuMDU5IDIuMDY0NjggMjQuODc1NiAyLjA2NDY4QzI0Ljc5MiAyLjA2NDY4IDI0LjY5MjYgMi4xMDUzIDI0LjU4NDggMi4xODk0QzI0LjMwMSAyLjQxMTQ2IDI0LjA1MDcgMi43MDI3NyAyMy44MjY3IDIuOTgzMTdDMjMuMzMwMyAzLjYwMjg0IDIyLjkwMTYgNC4yOTAxNCAyMi40NTcyIDQuOTQ3MkMyMS41Mzc1IDYuMzA2OTYgMjAuODUyOSA3Ljc3MzAxIDIwLjI2MDQgOS4yOTYzMUMxOS45ODk5IDkuOTkyNTYgMTkuNjcyMiAxMC42NDIxIDE5LjQ2NzUgMTEuMzU2NkMxOS4zNjc3IDExLjY4NjIgMTkuMjkyIDExLjgwNTggMTkuMjk4NCAxMS45ODkzWk0zMS44OTU2IDEzLjEzNzNDMzEuOTAwMyAxMy40OSAzMS43MDEzIDEzLjgyMTEgMzEuNTM4MyAxNC4xMjA0QzMxLjM1NzkgMTQuNDUzNCAzMS4xODk0IDE0Ljc5MjUgMzEuMDAzOCAxNS4xMjI3QzMwLjYxOTYgMTUuODA3NiAzMC4xNjQ5IDE2LjQ4OSAyOS42ODEzIDE3LjEwOEMyOC43NjMzIDE4LjI4MjUgMjcuNTg3MSAxOS4yNTgxIDI2LjE4MjggMTkuMjU4MUMyNS4zMjcxIDE5LjI1ODEgMjQuNzU0IDE4Ljg2MjMgMjQuNDkzOCAxOC40OTMzQzI0LjI5NTQgMTguMjExNyAyNC4xNzE5IDE3Ljg4ODIgMjQuMTAxOSAxNy41NTE4QzIzLjAwMjUgMTguODgxMSAyMS42MTA0IDIwIDIwLjAyNjEgMjBDMTkuMTY1IDIwIDE4LjY2MjIgMTkuODI1NSAxOC4wNDE2IDE5LjIxN0MxNy40NTAyIDE4LjYzNzIgMTcuMTQxOCAxNy44MTg5IDE3LjAwODYgMTYuOTg5MkMxNi45NzgyIDE3LjAyODkgMTYuOTQ4MSAxNy4wNjkgMTYuOTE3NSAxNy4xMDhDMTUuOTk5NiAxOC4yODI1IDE0LjgyMzQgMTkuMjU4MSAxMy40MTkxIDE5LjI1ODFDMTIuNTYzMiAxOS4yNTgxIDExLjk5MDMgMTguODYyMyAxMS43MzAxIDE4LjQ5MzNDMTEuNTMxNyAxOC4yMTE3IDExLjQwODIgMTcuODg4MiAxMS4zMzgyIDE3LjU1MThDMTAuMjM4OCAxOC44ODExIDguODQ2ODYgMjAgNy4yNjIxOCAyMEM2LjQwMTMxIDIwIDUuODk4NDkgMTkuODI1NSA1LjI3NzkzIDE5LjIxN0M0LjQyNTY0IDE4LjM4MTUgNC4xNTk5MiAxNy4wNTExIDQuMTYyNiAxNS45MDQ0QzQuMDkyMjggMTUuOTMyNSA0LjAxNTUxIDE1Ljk2NjcgMy45MzEwNSAxNi4wMDgyQzIuOTcwMTUgMTYuNTg1MSAxLjkzMzkyIDE3LjAzMDUgMC44MDA4NzUgMTcuMjc3M0MtMC4wMDQ3MDc0IDE3LjQ1MjYgLTAuMzQ1NTg2IDE2LjIxNjcgMC40NjAxNzYgMTYuMDQxM0MxLjc5NzU3IDE1Ljc1IDMuMjM4OTIgMTQuOTk0OSA0LjMxNjg1IDE0LjIzMjFDNC4zOTg5OCAxMy43MzkzIDQuNTA2NyAxMy4yNDkgNC42MjA1MSAxMi43NjEyQzQuODg1ODcgMTEuNjIyMyA1LjIxNDU4IDEwLjUxMzcgNS42MjM4MiA5LjQyMTEzQzYuMDM5NDkgOC4zNjAyIDYuNDExODYgNy4yNzgzNCA2Ljk1MDQ3IDYuMjY4OTRDNy4yMTY5MSA1Ljc2OTcxIDcuNDY0MiA1LjI4MTIgNy43MjQwMiA0Ljc3ODAzQzguMDAxMiA0LjI0MDMxIDguNDU5MSAzLjU3NTc0IDguNzg1ODQgMy4wNjg5OEM5LjUwNjA3IDEuOTUyMjIgMTAuODc3NSAwIDEyLjM5NjggMEMxMi44MDY4IDAgMTMuMjIxNyAwLjE1NjIxNCAxMy42Mjg1IDAuNTMxMjdDMTQuMDA1MyAwLjg3OTEyNyAxNC4xNTA4IDEuNDExODMgMTQuMTQ5IDEuOTI5MTRDMTQuMTQ1MSAyLjk5OTkxIDEzLjY5NjggNC4xODY0NSAxMy4yNzI2IDUuMjI1MDJDMTIuODQ2IDYuMjcwMiAxMi4yNDk2IDcuMzA3NTEgMTEuNjkxMyA4LjI4OTdDMTAuNTg4NyAxMC4yMjg3IDkuMTM4MzUgMTEuOTMyIDcuNjUwNjYgMTMuNTY2MUM3LjI3OTE4IDEzLjk2NjUgNi45MDI1MSAxNC4zNTkxIDYuNTAwNDQgMTQuNzI4OEM2LjA5MDg1IDE1LjEwNTUgNS44NTA1MyAxNS40Njg2IDUuODM5OTcgMTYuMDc0NkM1LjgzOTk3IDE3LjA1OTcgNi4wMjg5MyAxOC4zMjk2IDcuMjAwNjIgMTguMzI5NkM4LjIzNTYxIDE4LjMyOTYgOS4yMzk4MSAxNy4zMTcyIDkuODc1NTggMTYuNTk5OEMxMC42MzAzIDE1Ljc0NzkgMTEuMTQzMiAxNC45NDY4IDExLjcxOTQgMTMuOTY3M0MxMS44MTAzIDEzLjgxMjUgMTEuODg2NSAxMy42NjcyIDExLjk2IDEzLjUyMjZDMTIuMDM4OCAxMy4zMDkzIDEyLjExOTggMTMuMDk4NSAxMi4yMDE4IDEyLjg5MTFDMTIuNDQ3NyAxMi4yNjg4IDEyLjczMTQgMTEuNjY3IDEzLjAxMjIgMTEuMDYwNEMxMy4yNzEzIDEwLjUwMDEgMTMuNDcyNCA5LjgxNTUxIDEzLjk0NDMgOS4zODk2NEMxNC4xMzYxIDkuMjE2NzggMTQuNDA0MyA5LjExNTE1IDE0LjY2MjcgOS4xMTUxNUMxNS4xMjA2IDkuMTE1MTUgMTUuNDU2IDkuNDM2MzQgMTUuNDU2IDkuOTE1MThDMTUuNDU2IDEwLjM4NTQgMTUuMTMyNiAxMC44OTMxIDE0LjkzNjkgMTEuMjcwOEMxNC42MzQ1IDExLjg1NDIgMTQuMzU4MiAxMi40NDk3IDE0LjA4NTUgMTMuMDQ3M0MxMy43OTc3IDEzLjY3ODggMTMuNDk4NCAxNC4zMjAxIDEzLjMwNjcgMTQuOTg4M0MxMy4xNjUyIDE1LjQ4MDUgMTMuMDAyOSAxNi4xMDkgMTIuOTg2MyAxNi42NTZDMTIuOTcwMyAxNy4xNzA0IDEzLjE0MyAxNy40NjA5IDEzLjU0NzkgMTcuNDYwOUMxMy43ODQxIDE3LjQ2MDkgMTQuMDUzMSAxNy4zNTgxIDE0LjI2MzcgMTcuMjMxMUMxNC44NzIxIDE2Ljg2MzkgMTUuMzU2OCAxNi4zNTAyIDE1Ljc2MjMgMTUuNzgwNEMxNi4xNDkgMTUuMjM3NyAxNi40OTY1IDE0LjY4OTEgMTYuODQ2NSAxNC4xMjJDMTYuOTY0NiAxMy45MzExIDE3LjA5MzMgMTMuNzI1MSAxNy4yMTQ5IDEzLjUyMzFDMTcuMjY3NyAxMy4yNjg1IDE3LjMyNTIgMTMuMDE0NiAxNy4zODQyIDEyLjc2MTJDMTcuNjQ5NiAxMS42MjIzIDE3Ljk3ODMgMTAuNTEzNyAxOC4zODczIDkuNDIxMTNDMTguODAzMiA4LjM2MDIgMTkuMTc1OCA3LjI3ODM0IDE5LjcxNDIgNi4yNjg5NEMxOS45ODA2IDUuNzY5NzEgMjAuMjI3OSA1LjI4MTIgMjAuNDg3NiA0Ljc3ODAzQzIwLjc2NDkgNC4yNDAzMSAyMS4yMjI4IDMuNTc1NzQgMjEuNTQ5NiAzLjA2ODk4QzIyLjI3IDEuOTUyMjIgMjMuNjQxMiAwIDI1LjE2MDUgMEMyNS41NzAzIDAgMjUuOTg1NSAwLjE1NjIxNCAyNi4zOTIyIDAuNTMxMjdDMjYuNzY5IDAuODc5MTI3IDI2LjkxNDMgMS40MTE4MyAyNi45MTI3IDEuOTI5MTRDMjYuOTA4OCAyLjk5OTkxIDI2LjQ2MDUgNC4xODY0NSAyNi4wMzYzIDUuMjI1MDJDMjUuNjA5NyA2LjI3MDIgMjUuMDEzNSA3LjMwNzUxIDI0LjQ1NSA4LjI4OTdDMjMuMzUyNCAxMC4yMjg3IDIxLjkwMTkgMTEuOTMyIDIwLjQxNDQgMTMuNTY2MUMyMC4wNDI5IDEzLjk2NjUgMTkuNjY2MiAxNC4zNTkxIDE5LjI2NDEgMTQuNzI4OEMxOC44NTQ2IDE1LjEwNTUgMTguNjE0MiAxNS40Njg2IDE4LjYwMzcgMTYuMDc0NkMxOC42MDM3IDE3LjA1OTcgMTguNzkyNSAxOC4zMjk2IDE5Ljk2NDMgMTguMzI5NkMyMC45OTkzIDE4LjMyOTYgMjIuMDAzNSAxNy4zMTcyIDIyLjYzOTMgMTYuNTk5OEMyMy4zOTQxIDE1Ljc0NzkgMjMuOTA2OSAxNC45NDY4IDI0LjQ4MzEgMTMuOTY3M0MyNC41NzQgMTMuODEyNSAyNC42NTAyIDEzLjY2NzIgMjQuNzIzOCAxMy41MjI2QzI0LjgwMjUgMTMuMzA5MyAyNC44ODM1IDEzLjA5ODUgMjQuOTY1NSAxMi44OTExQzI1LjIxMTQgMTIuMjY4OCAyNS40OTUzIDExLjY2NyAyNS43NzU3IDExLjA2MDRDMjYuMDM1MiAxMC41MDAxIDI2LjIzNjEgOS44MTU1MSAyNi43MDggOS4zODk2NEMyNi45IDkuMjE2NzggMjcuMTY4MiA5LjExNTE1IDI3LjQyNjMgOS4xMTUxNUMyNy44ODQzIDkuMTE1MTUgMjguMjE5OSA5LjQzNjM0IDI4LjIxOTkgOS45MTUxOEMyOC4yMTk5IDEwLjM4NTQgMjcuODk2MyAxMC44OTMxIDI3LjcwMDQgMTEuMjcwOEMyNy4zOTgyIDExLjg1NDIgMjcuMTIxNyAxMi40NDk3IDI2Ljg0OTIgMTMuMDQ3M0MyNi41NjE1IDEzLjY3ODggMjYuMjYyMSAxNC4zMjAxIDI2LjA3MDQgMTQuOTg4M0MyNS45Mjg5IDE1LjQ4MDUgMjUuNzY2OCAxNi4xMDkgMjUuNzQ5OCAxNi42NTZDMjUuNzM0IDE3LjE3MDQgMjUuOTA2NyAxNy40NjA5IDI2LjMxMTcgMTcuNDYwOUMyNi41NDc5IDE3LjQ2MDkgMjYuODE2OCAxNy4zNTgxIDI3LjAyNzQgMTcuMjMxMUMyNy42MzU4IDE2Ljg2MzkgMjguMTIwNCAxNi4zNTAyIDI4LjUyNiAxNS43ODA0QzI4LjkxMjcgMTUuMjM3NyAyOS4yNiAxNC42ODkxIDI5LjYxMDIgMTQuMTIyQzI5Ljc3ODQgMTMuODQ5OSAyOS45Njg4IDEzLjU0NjkgMzAuMTI4NiAxMy4yNjk2QzMwLjI4OTMgMTIuOTg5OSAzMC4zODQxIDEyLjY5NzMgMzAuNjc3IDEyLjUzNzdDMzAuODQ3NiAxMi40NDQ4IDMwLjk5NjYgMTIuNDA2OSAzMS4xNDEgMTIuNDA2OUMzMS41MzIyIDEyLjQwNjkgMzEuODg5NCAxMi42ODY0IDMxLjg5NTYgMTMuMTM3M1pNMjguNDA0MyA2LjQ4NDk2QzI4LjU1OTIgNi41OTc4NyAyOC43NDcgNi42NDgxNSAyOC45MzY0IDYuNjQ4MTVDMjkuMTEyOSA2LjY0ODE1IDI5LjI5MTMgNi42MDQ2NyAyOS40NDYxIDYuNTI4NjJDMzAuMDEzNSA2LjI0OTY1IDMwLjUyNDkgNS4zNTI2MyAzMC41MjQ5IDQuNzAxNDhDMzAuNTI0OSAzLjk5NzcxIDMwLjA2NDUgMy41OTUyNyAyOS40OTcxIDMuNTk1MjdDMjkuMzU2OCAzLjU5NTI3IDI5LjIxMjkgMy42MjM5MSAyOS4wNzQ2IDMuNjg1MjhDMjguMzcxIDMuOTk3NzEgMjguMDIzMyA0Ljc1MDUgMjguMDIzMyA1LjQ4MzYyQzI4LjAyMzMgNS44NTA0NCAyOC4wODYxIDYuMjUyODggMjguNDA0MyA2LjQ4NDk2Wk0xNS4yNTk3IDUuNDgzNjJDMTUuMjU5NyA0Ljc1MDUgMTUuNjA3NiAzLjk5NzcxIDE2LjMxMSAzLjY4NTI4QzE2LjQ0OTMgMy42MjM5MSAxNi41OTMyIDMuNTk1MjcgMTYuNzMzNCAzLjU5NTI3QzE3LjMwMDkgMy41OTUyNyAxNy43NjE0IDMuOTk3NzEgMTcuNzYxNCA0LjcwMTQ4QzE3Ljc2MTQgNS4zNTI2MyAxNy4yNDk5IDYuMjQ5NjUgMTYuNjgyNiA2LjUyODYyQzE2LjUyNzcgNi42MDQ2NyAxNi4zNDkzIDYuNjQ4MTUgMTYuMTcyOCA2LjY0ODE1QzE1Ljk4MzMgNi42NDgxNSAxNS43OTU2IDYuNTk3ODcgMTUuNjQwNyA2LjQ4NDk2QzE1LjMyMjUgNi4yNTI4OCAxNS4yNTk3IDUuODUwNDQgMTUuMjU5NyA1LjQ4MzYyWiIgZmlsbD0id2hpdGUiLz4KPC9nPgo8ZGVmcz4KPGNsaXBQYXRoIGlkPSJjbGlwMF82NDUzXzMwOCI+CjxyZWN0IHdpZHRoPSIzMiIgaGVpZ2h0PSIyMCIgZmlsbD0id2hpdGUiLz4KPC9jbGlwUGF0aD4KPC9kZWZzPgo8L3N2Zz4K'; // trimmed for clarity

    $icon_html = '<span style="background-image:url(' . esc_attr($base64_svg) . '); background-size:contain; background-repeat:no-repeat; width:20px; height:20px; display:inline-block; position:relative; top:9px; margin-right:6px;"></span>';

    $wp_admin_bar->add_node(array(
        'id' => 'lili-integration',
        'title' => $icon_html . '<span class="ab-label">Business Banking</span>',
        'href' => admin_url('admin.php?page=lili-integration'),
        'meta' => array('title' => 'Go to Business Banking Dashboard'),
    ));

    $wp_admin_bar->add_node(array(
        'id' => 'lili-integration-open',
        'parent' => 'lili-integration',
        'title' => 'Open Account for Free',
        'href' => 'https://lp.lili.co/lili-for-ecommerce/?utm_medium=partners&utm_source=wprepo&utm_campaign=menu',
        'meta' => array('target' => '_blank', 'title' => 'Open an account at Lili'),
    ));

    // Child 1: FAQ
    $wp_admin_bar->add_node(array(
        'id' => 'lili-integration-faq',
        'parent' => 'lili-integration',
        'title' => 'Live balance and latest transactions',
        'href' => admin_url('admin.php?page=lili-integration'),
    ));

}

add_action('admin_bar_menu', 'lili_admin_bar_menu', 100);

function lili_add_dashboard_widget()
{
    wp_add_dashboard_widget(
        'lili_dashboard_widget',         // Widget slug
        'Business Banking by Lili',      // Title
        'lili_render_dashboard_widget'   // Callback function
    );
}

function lili_render_dashboard_widget()
{
    ?>
    <div style="padding-right: 10px;">
        <p><strong>Lili Business Banking Platform for eCommerce Merchants</strong></p>
        <p>Seamless banking, accounting, and taxes - all in one.</p>
        <ul>
            <li>- <strong>Advanced business checking account</strong><br>
                Lili Visa® Debit Card, express ACH, domestic & international wire transfers, with no hidden fees, no
                overdraft fees, and no minimum balance required.
            </li>
            <li>
                - <strong>Built-in accounting software</strong> including bill pay, invoicing, automated transaction
                categorization and reports
            </li>
            <li>- <strong>Automate tax savings</strong> throughout the year and receive auto-generated expense reports
                and pre-filled tax
                forms
            </li>
        </ul>
        <p style="font-size:9px;">Lili is a financial technology company, not a bank. Banking services provided by
            Sunrise Banks, N.A., Member
            FDIC.</p>
        <p style="font-size:9px;">Accounting, tax preparation and invoicing software is available to Lili Smart and Lili
            Premium account
            holders only; applicable monthly account fees apply. For details, please refer to your Sunrise Banks Account
            Agreement.</p>
        <p>
            <a href="https://lp.lili.co/lili-for-ecommerce/?utm_medium=partners&utm_source=wprepo&utm_campaign=dashboard"
               target="_blank"
               class="button button-primary">Open Account for Free</a>
        </p>
    </div>
    <?php
}

add_action('wp_dashboard_setup', 'lili_add_dashboard_widget');