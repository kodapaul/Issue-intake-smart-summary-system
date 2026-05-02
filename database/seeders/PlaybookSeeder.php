<?php

namespace Database\Seeders;

use App\Issue\Models\PlaybookEntry;
use Illuminate\Database\Seeder;

class PlaybookSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->entries() as $entry) {
            PlaybookEntry::query()->updateOrCreate(
                ["slug" => $entry["slug"]],
                $entry,
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function entries(): array
    {
        return [
            [
                "slug" => "login_registration",
                "name" => "Login & Registration",
                "description" =>
                    "Issues related to creating an account, signing in, account lockouts, and verification emails.",
                "triggers" => [
                    "login",
                    "log in",
                    "sign in",
                    "sign up",
                    "register",
                    "registration",
                    "can't log in",
                    "cant login",
                    "locked out",
                    "account locked",
                    "unable to access",
                    "verification email",
                ],
                "summary_template" =>
                    "Customer reports difficulty logging in or completing account registration.",
                "suggested_action" =>
                    "Verify the customer's account status (active / unverified / locked); review recent login attempts in the audit log; if locked from failed attempts, unlock and trigger a password-reset email; if unverified, resend the verification email and confirm receipt.",
                "troubleshooting_steps" => [
                    "Confirm the email address the customer is using (typo is the most common cause).",
                    "Look up the account status: active, pending verification, locked, or deleted.",
                    "Check the auth log for the last 5 sign-in attempts, including IP and outcome.",
                    "If locked due to repeated failed attempts, unlock the account and send a password reset link.",
                    "If unverified, resend the verification email and ask the customer to check spam.",
                ],
                "faqs" => [
                    [
                        "q" =>
                            "I didn't receive a verification email, what do I do?",
                        "a" =>
                            "Check your spam or promotions folder. If still missing after 5 minutes, request a new verification email from the login screen and confirm your address has no typos.",
                    ],
                    [
                        "q" => "Why is my account locked?",
                        "a" =>
                            "Accounts are auto-locked after 5 consecutive failed login attempts to prevent brute-force attacks. Wait 15 minutes or contact support to unlock.",
                    ],
                    [
                        "q" => "Can I use the same email for two accounts?",
                        "a" =>
                            "No. Each account requires a unique email address. If you need to change the email on your existing account, see Account Settings.",
                    ],
                ],
                "category_hint" => "support",
                "priority_hint" => "medium",
            ],
            [
                "slug" => "password_security",
                "name" => "Password & Security",
                "description" =>
                    "Password resets, changing passwords, two-factor authentication, and suspicious-activity alerts.",
                "triggers" => [
                    "password",
                    "forgot password",
                    "reset password",
                    "change password",
                    "2fa",
                    "two factor",
                    "two-factor",
                    "authenticator",
                    "security",
                    "suspicious",
                    "unauthorized",
                ],
                "summary_template" =>
                    "Customer needs help with password recovery, password change, or two-factor authentication.",
                "suggested_action" =>
                    "Confirm identity via account email; trigger password reset link or 2FA recovery flow; if customer reports suspicious activity, freeze the account, force re-authentication on all devices, and review recent activity for anomalies.",
                "troubleshooting_steps" => [
                    "Confirm the customer is the account owner (verify email, recent order, or last login location).",
                    "For forgotten passwords: trigger the password-reset email and confirm delivery.",
                    "For 2FA recovery: verify backup codes; if none, escalate to identity verification.",
                    "For suspicious activity: freeze the account, log the customer out of all devices, and audit recent transactions.",
                    "Document the resolution and any account restrictions applied.",
                ],
                "faqs" => [
                    [
                        "q" => "How do I reset my password?",
                        "a" =>
                            'Click "Forgot password?" on the login screen and enter your account email. A reset link will be sent within 2 minutes.',
                    ],
                    [
                        "q" =>
                            "I lost access to my 2FA device — can I still log in?",
                        "a" =>
                            "Use one of your backup codes if you saved them. If not, submit an identity verification request and our team will help you regain access within 24 hours.",
                    ],
                    [
                        "q" => "How do I enable two-factor authentication?",
                        "a" =>
                            "Go to Account Settings → Security → Enable 2FA. Scan the QR code with an authenticator app and save your backup codes in a safe place.",
                    ],
                ],
                "category_hint" => "support",
                "priority_hint" => "medium",
            ],
            [
                "slug" => "delivery_shipping",
                "name" => "Delivery & Shipping",
                "description" =>
                    "Late deliveries, missing packages, tracking issues, and shipping address changes.",
                "triggers" => [
                    "delivery",
                    "shipping",
                    "tracking",
                    "late",
                    "missing package",
                    "didn't arrive",
                    "not delivered",
                    "wrong address",
                    "shipped",
                ],
                "summary_template" =>
                    "Customer reports an issue with delivery, package tracking, or shipping address.",
                "suggested_action" =>
                    "Pull the order's shipping record and tracking number; verify carrier status; if marked delivered but missing, ask customer to check with neighbors and household; if tracking is stalled >48h, file a carrier inquiry; if address is wrong and order is unshipped, update and re-route.",
                "troubleshooting_steps" => [
                    "Look up the order ID and confirm the current tracking status with the carrier's API.",
                    "If the package is in transit and within the expected window, reassure the customer and share the latest scan.",
                    "If marked delivered but customer claims missing, ask them to check with household members and neighbors before filing a claim.",
                    "If tracking is stalled for more than 48 hours, file a carrier inquiry and notify the customer.",
                    "If the shipping address was wrong and the package is undelivered, attempt re-routing or recall.",
                ],
                "faqs" => [
                    [
                        "q" =>
                            'My tracking hasn\'t updated in 3 days, is my package lost?',
                        "a" =>
                            "Carrier scans can lag, especially in transit between facilities. If there's no update for 5+ business days, we'll file a trace request on your behalf.",
                    ],
                    [
                        "q" =>
                            "Can I change the delivery address after placing an order?",
                        "a" =>
                            "If the order has not yet been picked, yes — contact us within 1 hour of placing it. After pickup, the carrier may charge a re-routing fee.",
                    ],
                    [
                        "q" =>
                            "The tracking says 'delivered' but I didn't receive my package.",
                        "a" =>
                            "Check with anyone who shares your address and look for a doorbell-camera notification. If still missing after 24 hours, we will open a missing-package claim.",
                    ],
                ],
                "category_hint" => "support",
                "priority_hint" => "medium",
            ],
            [
                "slug" => "promo_codes",
                "name" => "Promo Codes & Discounts",
                "description" =>
                    "Applying promo codes, troubleshooting invalid codes, and questions about discount eligibility.",
                "triggers" => [
                    "promo",
                    "promo code",
                    "code",
                    "discount",
                    "coupon",
                    "voucher",
                    "apply code",
                    "code not working",
                    "expired",
                    "invalid code",
                ],
                "summary_template" =>
                    "Customer reports an issue applying a promo or discount code at checkout.",
                "suggested_action" =>
                    "Verify code validity (active, not expired, eligible for cart contents); if valid, check for case sensitivity, leading/trailing whitespace, or single-use already redeemed; if customer is otherwise eligible, manually apply the discount and document the override.",
                "troubleshooting_steps" => [
                    "Ask the customer to copy and paste the code (not type it) to rule out typos.",
                    "Verify the code is active and unexpired in the promotions panel.",
                    "Confirm the cart meets the code's eligibility rules (minimum order value, product category, customer segment).",
                    "Check whether the code is single-use and has been redeemed previously by this account.",
                    "If valid and unused, manually apply the discount and explain the root cause to the customer.",
                ],
                "faqs" => [
                    [
                        "q" => "Can promo codes be combined?",
                        "a" =>
                            "Most codes cannot be stacked. Only the highest-value code applies; the rest are ignored at checkout.",
                    ],
                    [
                        "q" => "Why does my code say 'invalid'?",
                        "a" =>
                            "Most common causes: the code is expired, already used, doesn't meet the minimum order value, or has a typo. Codes are case-sensitive.",
                    ],
                    [
                        "q" =>
                            "Can I apply a code after I already placed my order?",
                        "a" =>
                            "If the code was valid at the time of order, yes — we can apply it as a credit. Reach out within 7 days of the order with your order number.",
                    ],
                ],
                "category_hint" => "support",
                "priority_hint" => "low",
            ],
            [
                "slug" => "payment_billing",
                "name" => "Payment & Billing",
                "description" =>
                    "Failed payments, double charges, refunds, and disputed transactions.",
                "triggers" => [
                    "payment",
                    "billing",
                    "charged",
                    "refund",
                    "double charge",
                    "card declined",
                    "invoice",
                    "credit card",
                    "debit",
                    "transaction",
                    "chargeback",
                ],
                "summary_template" =>
                    "Customer reports a payment, billing, or refund issue.",
                "suggested_action" =>
                    "Pull the transaction record and gateway response; if declined, check for AVS/CVV mismatch or insufficient funds and ask customer to confirm with their bank; if double-charged, refund the duplicate; if disputed, escalate to billing team within 24 hours.",
                "troubleshooting_steps" => [
                    "Look up the transaction by order ID or last 4 digits of the card.",
                    "Read the gateway response code: declined, AVS mismatch, CVV failure, insufficient funds, etc.",
                    "For declines: ask the customer to verify card details and try again, or use a different payment method.",
                    "For double-charges: confirm both charges are for the same order, then refund the duplicate immediately.",
                    "For disputes/chargebacks: gather order details, communication history, and escalate to the billing team.",
                ],
                "faqs" => [
                    [
                        "q" => "Why was my card declined?",
                        "a" =>
                            "Common reasons: incorrect CVV or zip code, insufficient funds, expired card, or the bank flagging the transaction as unusual. Contact your bank to confirm before retrying.",
                    ],
                    [
                        "q" => "How long do refunds take?",
                        "a" =>
                            "Refunds are issued same-day on our side, but most banks take 3-5 business days to reflect them on your statement.",
                    ],
                    [
                        "q" =>
                            "I see two charges for the same order — what happened?",
                        "a" =>
                            "Sometimes a payment is authorized twice but only one captures. The pending one will drop off in 3-7 days. If both have settled, we will refund the duplicate.",
                    ],
                ],
                "category_hint" => "incident",
                "priority_hint" => "high",
            ],
            [
                "slug" => "orders_returns",
                "name" => "Orders & Returns",
                "description" =>
                    "Order status checks, cancellations, returns, and exchanges.",
                "triggers" => [
                    "order",
                    "order status",
                    "cancel order",
                    "return",
                    "exchange",
                    "refund order",
                    "wrong item",
                    "missing item",
                    "damaged",
                ],
                "summary_template" =>
                    "Customer needs help with order status, cancellation, return, or exchange.",
                "suggested_action" =>
                    "Look up the order and confirm its current state; if pre-shipment, cancellation is free and immediate; if shipped, advise on return process and generate a prepaid label; for damaged or wrong items, request photos, escalate to fulfillment, and offer replacement or refund.",
                "troubleshooting_steps" => [
                    "Pull the order by ID and confirm its current status (placed, picked, shipped, delivered).",
                    "If pre-shipment and customer wants to cancel: cancel immediately and refund automatically.",
                    "If shipped and customer wants to return: generate a return label and send return instructions.",
                    "If item is damaged or incorrect: request photos before approving replacement or refund.",
                    "Document the resolution and update the order notes.",
                ],
                "faqs" => [
                    [
                        "q" => "Can I cancel my order?",
                        "a" =>
                            "Yes, if it has not yet been shipped. Cancel from the order page or contact support within 1 hour of placing it.",
                    ],
                    [
                        "q" => "What is your return policy?",
                        "a" =>
                            "Most items can be returned within 30 days of delivery in their original condition. Some categories (perishables, custom items) are final sale.",
                    ],
                    [
                        "q" => "I received the wrong item — what should I do?",
                        "a" =>
                            "Contact support with photos of the item received and the packing slip. We will send a replacement and arrange free pickup of the wrong item.",
                    ],
                ],
                "category_hint" => "support",
                "priority_hint" => "medium",
            ],
            [
                "slug" => "account_settings",
                "name" => "Account Settings",
                "description" =>
                    "Updating email, phone, profile information, or deleting an account.",
                "triggers" => [
                    "update email",
                    "change email",
                    "change phone",
                    "update profile",
                    "delete account",
                    "close account",
                    "privacy",
                    "data export",
                ],
                "summary_template" =>
                    "Customer wants to update their account information or close their account.",
                "suggested_action" =>
                    "Confirm identity; for email/phone changes, send a verification link or code to the new address before changing; for account deletion, explain consequences (data retention, active subscriptions, refund eligibility) and require explicit confirmation; for data export, generate the export within 7 days.",
                "troubleshooting_steps" => [
                    "Verify the customer is the account owner.",
                    "For email/phone changes: send a verification link or code to the new contact and only update upon confirmation.",
                    "For deletions: warn the customer about consequences (loss of order history, active subscriptions, etc.) and require written confirmation.",
                    "For data export: queue a GDPR/CCPA export job; deliver download link within 7 days.",
                    "Log the change for audit purposes.",
                ],
                "faqs" => [
                    [
                        "q" => "How do I change my email address?",
                        "a" =>
                            "Go to Account Settings → Profile → Email. Enter the new address; we will send a verification link there. Click it within 24 hours to confirm the change.",
                    ],
                    [
                        "q" => "What happens when I delete my account?",
                        "a" =>
                            "Your profile is removed, active subscriptions are cancelled, and order history is anonymized. We retain transaction records as required by tax law.",
                    ],
                    [
                        "q" => "Can I export my data?",
                        "a" =>
                            "Yes. Go to Account Settings → Privacy → Request Data Export. We will email you a download link within 7 days.",
                    ],
                ],
                "category_hint" => "support",
                "priority_hint" => "low",
            ],
            [
                "slug" => "app_site_issues",
                "name" => "App & Site Issues",
                "description" =>
                    "Crashes, slow performance, broken pages, and errors loading the app or website.",
                "triggers" => [
                    "crash",
                    "slow",
                    "loading",
                    "blank",
                    "error",
                    "broken",
                    "glitch",
                    "freeze",
                    "stuck",
                    "not loading",
                    "white screen",
                    "app issue",
                ],
                "summary_template" =>
                    "Customer reports a technical issue with the app or website (crash, slow loading, broken page).",
                "suggested_action" =>
                    "Capture device, OS version, app version, and browser; check status page for known issues; if isolated, ask customer to clear cache and reinstall; if pattern, file a bug ticket with frontend or platform team and add the customer to the affected list.",
                "troubleshooting_steps" => [
                    "Capture the technical context: device, OS version, app version, browser (if web), and the specific page or action.",
                    "Check the status page for any active incidents.",
                    "Ask the customer to clear cache, refresh, or reinstall the app.",
                    "Try to reproduce on staging or in your own environment.",
                    "If reproducible or pattern-matched: file a bug ticket with logs and add the customer to the impact list.",
                ],
                "faqs" => [
                    [
                        "q" => "The app keeps crashing — what should I do?",
                        "a" =>
                            "Update to the latest app version, force-close and reopen, then reinstall if it persists. If still crashing, send us your device model and OS version.",
                    ],
                    [
                        "q" =>
                            "Pages are loading very slowly. Is this on your end?",
                        "a" =>
                            "Check our status page for ongoing incidents. If everything looks green, try a different network (Wi-Fi vs cellular) or browser to isolate the issue.",
                    ],
                    [
                        "q" =>
                            "I'm seeing a blank white screen — what's wrong?",
                        "a" =>
                            "Often a cache issue. Hard-refresh (Ctrl/Cmd + Shift + R) or clear your browser's site data for our domain. If the issue persists, send us a screenshot and the URL.",
                    ],
                ],
                "category_hint" => "bug",
                "priority_hint" => "medium",
            ],
            [
                "slug" => "feature_requests",
                "name" => "Feature Requests",
                "description" =>
                    "Customer suggestions for new features, integrations, or improvements.",
                "triggers" => [
                    "feature",
                    "suggestion",
                    "could you add",
                    "would be nice",
                    "missing",
                    "integration",
                    "wish you had",
                    "feature request",
                ],
                "summary_template" =>
                    "Customer suggests a new feature, integration, or improvement.",
                "suggested_action" =>
                    "Thank the customer for the feedback; tag the request in the product backlog under the right area; if a similar request exists, link to it and notify the customer when planned; otherwise, share the public roadmap link if relevant.",
                "troubleshooting_steps" => [
                    "Acknowledge the suggestion and thank the customer specifically for what they're proposing.",
                    "Search the product backlog for similar existing requests.",
                    "If found: link the customer to the existing request and confirm they will be notified on progress.",
                    'If new: create a backlog entry tagged appropriately, capture the use case in the customer\'s words.',
                    "Share the public roadmap link if it's relevant to their suggestion.",
                ],
                "faqs" => [
                    [
                        "q" => "How do I submit a feature idea?",
                        "a" =>
                            "You can email support, post in our community forum, or use the in-app feedback button. We review all suggestions weekly.",
                    ],
                    [
                        "q" => "Will my suggestion actually be built?",
                        "a" =>
                            'We prioritize features by user demand, strategic fit, and effort. We can\'t commit to building every suggestion, but we genuinely read all of them.',
                    ],
                    [
                        "q" => "Can you tell me when feature X will ship?",
                        "a" =>
                            "Check our public roadmap for committed features. For backlog items, we don't share dates because priorities can shift, but we'll notify you when an item you upvoted moves to in-progress.",
                    ],
                ],
                "category_hint" => "feature_request",
                "priority_hint" => "low",
            ],
            [
                "slug" => "general_inquiry",
                "name" => "General Inquiries",
                "description" =>
                    "How-to questions, definitions, navigation help, and general informational requests.",
                "triggers" => [
                    "how do i",
                    "how to",
                    "where is",
                    "what is",
                    "explain",
                    "help me understand",
                    "general",
                    "question",
                ],
                "summary_template" =>
                    "Customer asking a general how-to or informational question.",
                "suggested_action" =>
                    "Identify the topic the customer is asking about; search the help center for an existing article and link it; if no article exists, write a brief inline answer and flag the topic for documentation team to create an article.",
                "troubleshooting_steps" => [
                    "Identify the specific topic of the question.",
                    "Search the help center for an existing answer.",
                    "If found: share the link and quote the relevant section.",
                    "If not found: write a clear inline answer and flag the topic to the documentation team.",
                    "Follow up to confirm the customer's question is answered.",
                ],
                "faqs" => [
                    [
                        "q" => "Where can I find your help center?",
                        "a" =>
                            "Our help center is at /help and is also reachable from the in-app menu under Help & Support.",
                    ],
                    [
                        "q" => "How do I contact a real person?",
                        "a" =>
                            "Email support directly or use live chat (available weekdays 9am-9pm). For urgent issues, mark your message as urgent and we will respond within an hour.",
                    ],
                    [
                        "q" => "Do you have a phone number?",
                        "a" =>
                            "We don't offer phone support so we can keep response times consistent in writing, but live chat is the fastest channel for time-sensitive issues.",
                    ],
                ],
                "category_hint" => "other",
                "priority_hint" => "low",
            ],
        ];
    }
}
