<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\variables;

use enupal\stripe\elements\Order;
use enupal\stripe\elements\PaymentForm;
use enupal\stripe\enums\FrequencyType;
use enupal\stripe\services\PaymentForms;
use enupal\stripe\Stripe;
use craft\helpers\Template as TemplateHelper;
use DateTime;
use Craft;

/**
 * Stripe Payments provides an API for accessing information about stripe buttons. It is accessible from templates via `craft.enupalStripe`.
 *
 */
class StripeVariable
{
    /**
     * @var Order
     */
    public $orders;

    public function __construct()
    {
        $this->orders = Order::find();
    }

    /**
     * @return string
     */
    public function getName()
    {
        $plugin = Stripe::$app->settings->getPlugin();

        return $plugin->getName();
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        $plugin = Stripe::$app->settings->getPlugin();

        return $plugin->getVersion();
    }

    /**
     * @return string
     */
    public function getSettings()
    {
        return Stripe::$app->settings->getSettings();
    }

    /**
     * @return array|null
     */
    public function getConfigSettings()
    {
        return Stripe::$app->settings->getConfigSettings();
    }

    /**
     * Returns a complete Payment Form for display in template
     *
     * @param string $handle
     * @param array|null $options
     *
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function paymentForm($handle, array $options = null)
    {
        return Stripe::$app->paymentForms->getPaymentFormHtml($handle, $options);
    }

    /**
     * @return array
     */
    public function getCurrencyIsoOptions()
    {
        return Stripe::$app->paymentForms->getIsoCurrencies();
    }

    /**
     * @return array
     */
    public function getCurrencyOptions()
    {
        return Stripe::$app->paymentForms->getCurrencies();
    }

    /**
     * @return array
     */
    public function getLanguageOptions()
    {
        return Stripe::$app->paymentForms->getLanguageOptions();
    }

    /**
     * @return array
     */
    public function getDiscountOptions()
    {
        return Stripe::$app->paymentForms->getDiscountOptions();
    }

    /**
     * @return array
     */
    public function getAmountTypeOptions()
    {
        return Stripe::$app->paymentForms->getAmountTypeOptions();
    }

    /**
     * @return array
     */
    public function getOrderStatuses()
    {
        $statuses = Stripe::$app->orderStatuses->getAllOrderStatuses();
        $options = [];
        foreach ($statuses as $status) {
            $options[$status->id] = Stripe::t($status->name);
        }

        return $options;
    }

    /**
     * @return array
     */
    public function getFrequencyOptions()
    {
        $options = [];
        $options[FrequencyType::YEAR] = Stripe::t('Year');
        $options[FrequencyType::MONTH] = Stripe::t('Month');
        $options[FrequencyType::WEEK] = Stripe::t('Week');
        $options[FrequencyType::DAY] = Stripe::t('Day');

        return $options;
    }

    /**
     * @return array
     */
    public function getSubscriptionsTypes()
    {
        $options = Stripe::$app->paymentForms->getSubscriptionsTypes();

        return $options;
    }

    /**
     * @return array
     */
    public function getSubscriptionsPlans()
    {
        $options = Stripe::$app->paymentForms->getSubscriptionsTypes();

        return $options;
    }

    /**
     * @param $paymentForm PaymentForm
     * @param $block
     * @return \Twig_Markup
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public function displayField($paymentForm, $block)
    {
        $templatePaths = Stripe::$app->paymentForms->getFormTemplatePaths($paymentForm);
        $view = Craft::$app->getView();
        $defaultTemplate = Stripe::$app->paymentForms->getEnupalStripePath() . DIRECTORY_SEPARATOR . 'fields';
        $view->setTemplatesPath($defaultTemplate);
        $preValue = '';

        $inputFilePath = $templatePaths['fields'] . DIRECTORY_SEPARATOR . strtolower($block->type);

        $this->setTemplateOverride($view, $inputFilePath, $templatePaths['fields']);

        if ($block->type == 'hidden') {
            if ($block->hiddenValue) {
                try {
                    $preValue = Craft::$app->view->renderObjectTemplate($block->hiddenValue, Stripe::$app->paymentForms->getFieldVariables());
                } catch (\Exception $e) {
                    Craft::error($e->getMessage(), __METHOD__);
                }
            }
        }

        $htmlField = $view->renderTemplate(
            strtolower($block->type), [
                'block' => $block,
                'preValue' => $preValue
            ]
        );

        $view->setTemplatesPath(Craft::$app->path->getSiteTemplatesPath());

        return TemplateHelper::raw($htmlField);
    }


    /**
     * @param $block mixed
     *
     * @return string
     * @throws \Exception
     */
    public function labelToHandle($block)
    {
        $label = $block->label ?? Stripe::$app->orders->getRandomStr();
        $handleFromUser = $block->fieldHandle ?? $label;

        $handle = Stripe::$app->paymentForms->labelToHandle($handleFromUser);

        return $handle;
    }

    /**
     * Display plans as dropdown or radio buttons to the user
     *
     * @param $paymentForm PaymentForm
     *
     * @return \Twig_Markup
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function displayMultiSelect($paymentForm)
    {
        $type = $paymentForm->subscriptionStyle;
        $matrix = $paymentForm->enupalMultiplePlans;

        $templatePaths = Stripe::$app->paymentForms->getFormTemplatePaths($paymentForm);
        $view = Craft::$app->getView();
        $defaultTemplate = Stripe::$app->paymentForms->getEnupalStripePath() . DIRECTORY_SEPARATOR . 'fields';
        $view->setTemplatesPath($defaultTemplate);

        $inputFilePath = $templatePaths['multipleplans'] . DIRECTORY_SEPARATOR . strtolower($type);

        $this->setTemplateOverride($view, $inputFilePath, $templatePaths['multipleplans']);

        $htmlField = $view->renderTemplate(
            strtolower($type), [
                'matrixField' => $matrix
            ]
        );

        $view->setTemplatesPath(Craft::$app->path->getSiteTemplatesPath());

        return TemplateHelper::raw($htmlField);
    }

    /**
     * Display plans as dropdown or radio buttons to the user
     *
     * @param $paymentForm PaymentForm
     *
     * @return \Twig_Markup
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function displayAddress($paymentForm)
    {
        $templatePaths = Stripe::$app->paymentForms->getFormTemplatePaths($paymentForm);
        $view = Craft::$app->getView();
        $defaultTemplate = Stripe::$app->paymentForms->getEnupalStripePath() . DIRECTORY_SEPARATOR . 'fields';
        $view->setTemplatesPath($defaultTemplate);

        $view->setTemplatesPath($templatePaths['address']);

        $htmlField = $view->renderTemplate(
            'address', [
                'paymentForm' => $paymentForm
            ]
        );

        $view->setTemplatesPath(Craft::$app->path->getSiteTemplatesPath());

        return TemplateHelper::raw($htmlField);
    }

    /**
     * @param $planId
     * @return null|string
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     */
    public function getDefaultPlanName($planId)
    {
        $plan = Stripe::$app->plans->getStripePlan($planId);
        $planName = null;

        if ($plan) {
            $planName = Stripe::$app->plans->getDefaultPlanName($plan);
        }

        return $planName;
    }

    /**
     * @param $number
     * @return array|\craft\base\ElementInterface
     */
    public function getOrderByNumber($number)
    {
        $order = Stripe::$app->orders->getOrderByNumber($number);

        return $order;
    }

    /**
     * @param $id
     * @return array|\craft\base\ElementInterface
     */
    public function getOrderById($id)
    {
        $order = Stripe::$app->orders->getOrderById($id);

        return $order;
    }

    /**
     * @return \enupal\stripe\elements\Order[]|null
     */
    public function getAllOrders()
    {
        $orders = Stripe::$app->orders->getAllOrders();

        return $orders;
    }

    /**
     * @param array $variables
     */
    public function addVariables(array $variables)
    {
        PaymentForms::addVariables($variables);
    }

    /**
     * @param $email
     * @return null|string
     */
    public function getCustomerReference($email)
    {
        $customerId = Stripe::$app->orders->getCustomerReference($email);

        return $customerId;
    }

    /**
     * @param $paymentTypeOptions
     * @return array
     */
    public function getPaymentTypesAsOptions($paymentTypeOptions)
    {
        return Stripe::$app->paymentForms->getPaymentTypesAsOptions($paymentTypeOptions);
    }

    /**
     * @return array
     */
    public function getSofortCountriesAsOptions()
    {
        return Stripe::$app->paymentForms->getSofortCountriesAsOptions();
    }

    /**
     * @return array
     */
    public function getAllOrderStatuses()
    {
        return Stripe::$app->orderStatuses->getAllOrderStatuses();
    }

    /**
     * @param $orderId
     * @return array|\enupal\stripe\records\Message[]|null
     */
    public function getAllMessages($orderId)
    {
        return Stripe::$app->messages->getAllMessages($orderId);
    }

    /**
     * @return string
     * @throws \yii\db\Exception
     */
    public function getOrderCurrencies()
    {
        return Stripe::$app->orders->getOrderCurrencies();
    }

    /**
     * @param $handle
     * @return PaymentForm|null
     */
    public function getPaymentForm($handle)
    {
        return Stripe::$app->paymentForms->getPaymentFormBySku($handle);
    }

    /**
     * @param $settings
     * @return mixed
     */
    public function getPaymentFormsAsElementOptions($settings)
    {
        $variables['elementType'] = PaymentForm::class;
        $variables['paymentFormElements'] = null;

        if ($settings->syncDefaultFormId) {
            $paymentForms = $settings->syncDefaultFormId;
            if (is_string($paymentForms)) {
                $paymentForms = json_decode($settings->syncDefaultFormId);
            }

            $paymentFormElements = [];

            if (count($paymentForms)) {
                foreach ($paymentForms as $key => $paymentFormId) {
                    $paymentForm = Craft::$app->elements->getElementById($paymentFormId);
                    array_push($paymentFormElements, $paymentForm);
                }

                $variables['paymentFormElements'] = $paymentFormElements;
            }
        }

        return $variables;
    }

    /**
     * @return array
     */
    public function getSyncTypes()
    {
        $options = [
            1 => Craft::t('enupal-stripe', 'One-Time'),
            2 => Craft::t('enupal-stripe', 'Subscriptions')
        ];

        return $options;
    }

    /**
     * @return array
     */
    public function getOrderStatusesAsOptions()
    {
        $statuses = Stripe::$app->orderStatuses->getAllOrderStatuses();
        $statusArray = [];

        foreach ($statuses as $status) {
            $statusArray[$status['id']] = $status['name'];
        }

        return $statusArray;
    }

    /**
     * @param $string
     *
     * @return DateTime
     */
    public function getDate($string)
    {
        return new DateTime($string, new \DateTimeZone(Craft::$app->getTimeZone()));
    }

    /**
     * @param null $userId
     * @return array|\craft\base\ElementInterface|null
     */
    public function getSubscriptionsByUser($userId = null)
    {
        if (is_null($userId)){
            $currentUser = Craft::$app->getUser()->getIdentity();
            $userId = $currentUser->id ?? null;
        }

        return Stripe::$app->subscriptions->getSubscriptionsByUser($userId);
    }

    /**
     * @param null $email
     * @return array|\craft\base\ElementInterface|null
     */
    public function getSubscriptionsByEmail($email = null)
    {
        if (is_null($email)){
            $currentUser = Craft::$app->getUser()->getIdentity();
            $email = $currentUser->email ?? null;
        }

        return Stripe::$app->subscriptions->getSubscriptionsByEmail($email);

    }

    /**
     * @param null $userId
     * @return array|\craft\base\ElementInterface|null
     */
    public function getOrdersByUser($userId = null)
    {
        if (is_null($userId)){
            $currentUser = Craft::$app->getUser()->getIdentity();
            $userId = $currentUser->id ?? null;
        }

        return Stripe::$app->orders->getSubscriptionsByUser($userId);
    }

    /**
     * @param null $email
     * @return array|\craft\base\ElementInterface|null
     */
    public function getOrdersByEmail($email = null)
    {
        if (is_null($email)){
            $currentUser = Craft::$app->getUser()->getIdentity();
            $email = $currentUser->email ?? null;
        }

        return Stripe::$app->orders->getSubscriptionsByEmail($email);

    }

    /**
     * @return bool
     */
    public function getIsSnapshotInstalled()
    {
        $plugin = Craft::$app->getPlugins()->getPlugin('enupal-snapshot');

        if (is_null($plugin)){
            return false;
        }

        return true;

    }

    /**
     * @param $view
     * @param $inputFilePath
     * @param $templatePath
     */
    private function setTemplateOverride($view, $inputFilePath, $templatePath)
    {
        // Allow input field templates to be overridden
        foreach (Craft::$app->getConfig()->getGeneral()->defaultTemplateExtensions as $extension) {
            if (file_exists($inputFilePath . '.' . $extension)) {

                // Override Field Input template path
                $view->setTemplatesPath($templatePath);
                break;
            }
        }
    }
}

