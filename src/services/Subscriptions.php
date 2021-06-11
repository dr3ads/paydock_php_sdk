<?php
namespace Paydock\Sdk;

use Paydock\Tools\ServiceHelper;
use Paydock\Tools\JsonTools;
use Paydock\Tools\UrlTools;

/*
 * This file is part of the Paydock.Sdk package.
 *
 * (c) Paydock
 *
 * For the full copyright and license information, please view
 * the LICENSE file which was distributed with this source code.
 */
final class Subscriptions
{
    private $chargeData;
    private $token;
    private $subscriptionId;
    private $paymentSourceData = array();
    private $customerData = array();
    private $scheduleData = array();
    private $subscriptionFilter;
    private $action;
    private $meta;
    private $actionMap = ["create" => "POST", "update" => "POST", "get" => "GET", "delete" => "DELETE"];

    public function create($amount, $currency, $description = "", $reference = "")
    {
        $this->action = "create";
        $this->chargeData = ["amount" => $amount, "currency"=>$currency, "description"=>$description, "reference" => $reference];
        return $this;
    }

    public function update($subscriptionId, $amount, $currency = "", $description = "", $reference = "", $paymentSourceId = "")
    {
        $this->action = "update";
        $this->subscriptionId = $subscriptionId;
        $this->chargeData = ["amount" => $amount, "currency"=>$currency, "description"=>$description, "reference" => $reference, "payment_source_id" => $paymentSourceId];
        return $this;
    }

    public function get()
    {
        $this->action = "get";
        return $this;
    }

    public function delete($subscriptionId)
    {
        $this->action = "delete";
        $this->subscriptionId = $subscriptionId;
        return $this;
    }

    public function withSubscriptionId($subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;
        return $this;
    }

    public function withParameters($filter)
    {
        $this->subscriptionFilter = $filter;
        return $this;
    }

    public function withToken($token)
    {
        $this->token = $token;
        return $this;
    }

    public function withCreditCard($gatewayId, $cardNumber, $expireYear, $expireMonth, $cardHolderName, $ccv)
    {
        $this->paymentSourceData = ["gateway_id" => $gatewayId, "card_number" => $cardNumber, "expire_month" => $expireMonth, "expire_year" => $expireYear, "card_name" => $cardHolderName, "card_ccv" => $ccv];
        return $this;
    }

    public function withBankAccount($gatewayId, $accountName, $accountBsb, $accountNumber, $accountHolderType = "", $accountBankName = "")
    {
        $this->paymentSourceData = ["gateway_id" => $gatewayId, "type" => "bank_account", "account_name" => $accountName, "account_bsb" => $accountBsb, "account_number" => $accountNumber, "account_holder_type" => $accountHolderType, "account_bank_name" => $accountBankName, "type" => "bsb"];
        return $this;
    }

    public function withCustomerId($customerId)
    {
        $this->customerId = $customerId;
        return $this;
    }

    public function withSchedule($interval, $frequency, $startDate = null, $endDate = null, $endAmountAfter = null, $endAmountBefore = null, $endAmountTotal = null, $endTransactions = null)
    {
        $this->scheduleData = ["interval" => $interval, "frequency" => $frequency, "start_date" => $startDate, "end_date" => $endDate, "end_amount_after" => $endAmountAfter, "end_amount_before" => $endAmountBefore, "end_amount_total" => $endAmountTotal, "end_transactions" => $endTransactions];
        return $this;
    }

    public function includeCustomerDetails($firstName, $lastName, $email, $phone)
    {
        $this->customerData += ["first_name" => $firstName, "last_name" => $lastName, "email" => $email, "phone" => $phone];
        return $this;
    }

    public function includeAddress($addressLine1, $addressLine2, $addressState, $addressCountry, $addressCity, $addressPostcode)
    {
        $this->paymentSourceData += ["address_line1" => $addressLine1, "address_line2" => $addressLine2, "address_state" => $addressState, "address_country" => $addressCountry, "address_city" => $addressCity, "address_postcode" => $addressPostcode];
        return $this;
    }

    public function includeMeta($meta)
    {
        $this->meta = $meta;
        return $this;
    }

    private function buildCreateJson()
    {
        $arrayData = [
            'amount'      => $this->chargeData["amount"],
            'currency'    => $this->chargeData["currency"],
            'reference'   => $this->chargeData["reference"],
            'description'   => $this->chargeData["description"]
        ];

        if (!empty($this->token)) {
            $arrayData += ["token" => $this->token];
        } else if (!empty($this->customerId)) {
            $arrayData += ["customer_id" => $this->customerId];
        }

        if (!empty($this->customerData)) {
            $arrayData += ["customer" => $this->customerData];
        }

        if (!empty($this->paymentSourceData)) {
            if (empty($arrayData["customer"])) {
                $arrayData["customer"] = array();
            }
            $arrayData["customer"]["payment_source"] = $this->paymentSourceData;
        }

        if (!empty($this->scheduleData)) {
            $arrayData += ["schedule" => $this->scheduleData];
        }

        if (!empty($this->meta)) {
            $arrayData += ["meta" => $this->meta];
        }

        $jsonTools = new JsonTools();
        $arrayData = $jsonTools->CleanArray($arrayData);

        return json_encode($arrayData);
    }

    private function buildUpdateJson()
    {
        $arrayData = [
            'amount'      => $this->chargeData["amount"],
            'reference'   => $this->chargeData["reference"],
            'description'   => $this->chargeData["description"],
            'payment_source_id'   => $this->chargeData["payment_source_id"]
        ];

        if (!empty($this->schedule)) {
            $arrayData += ["schedule" => $this->scheduleData];
        }

        $jsonTools = new JsonTools();
        $arrayData = $jsonTools->CleanArray($arrayData);

        return json_encode($arrayData);
    }

    private function buildJson()
    {
        switch ($this->action) {
            case "create":
                return $this->buildCreateJson();
            case "update":
                return $this->buildUpdateJson();
        }

        return "";
    }

    private function buildUrl()
    {
        $urlTools = new UrlTools();

        switch ($this->action) {
            case "update":
                return "subscriptions/" . urlencode($this->subscriptionId);
            case "get":
                return $urlTools->BuildQueryStringUrl("subscriptions", $this->subscriptionId, $this->subscriptionFilter);
            case "delete":
                return "subscriptions/" . urlencode($this->subscriptionId);
        }

        return "subscriptions";
    }

    public function call()
    {
        $data = $this->buildJson();
        $url = $this->buildUrl();

        return ServiceHelper::privateApiCall($this->actionMap[$this->action], $url, $data);
    }
}