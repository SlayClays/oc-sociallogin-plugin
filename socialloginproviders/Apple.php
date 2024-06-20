<?php namespace Flynsarmy\SocialLogin\SocialLoginProviders;

use Backend\Widgets\Form;
use Flynsarmy\SocialLogin\SocialLoginProviders\SocialLoginProviderBase;
use URL;

class Apple extends SocialLoginProviderBase
{
	use \October\Rain\Support\Traits\Singleton;
	protected $driver = 'apple';

	protected $callback;
	protected $adapter;

	/**
	 * Initialize the singleton free from constructor parameters.
	 */
	protected function init()
	{
		parent::init();

        $this->callback = URL::route('flynsarmy_sociallogin_provider_callback', ['Apple'], true);

	}

	public function getAdapter()
    {
        if ( !$this->adapter )
        {
            // Instantiate adapter using the configuration from our settings page
            $providers = $this->settings->get('providers', []);

            $this->adapter = new \Hybridauth\Provider\Apple([
                'callback' => $this->callback,

                'keys' => [
                    'team_id'     => @$providers['Apple']['team_id'],
                    'id'          => @$providers['Apple']['client_id'],
                    'key_id'      => @$providers['Apple']['key_id'],
                    'key_content' => @$providers['Apple']['key_content'],
                ],

                'scope' => 'name email',

                'debug_mode' => config('app.debug', false),
                'debug_file' => storage_path('logs/flynsarmy.sociallogin.'.basename(__FILE__).'.log'),
            ]);
        }

        return $this->adapter;
    }

	public function isEnabled()
	{
		$providers = $this->settings->get('providers', []);

		return !empty($providers['Apple']['enabled']);
	}

    public function isEnabledForBackend()
    {
        $providers = $this->settings->get('providers', []);

        return !empty($providers['Apple']['enabledForBackend']);
    }

	public function extendSettingsForm(Form $form)
	{
		$form->addFields([
			'noop' => [
				'type' => 'partial',
				'path' => '$/flynsarmy/sociallogin/partials/backend/forms/settings/_apple_info.htm',
				'tab' => 'Apple',
			],

			'providers[Apple][enabled]' => [
				'label' => 'Enabled on frontend?',
				'type' => 'checkbox',
                'comment' => 'Can frontend users log in with Apple?',
                'default' => 'true',
				'span' => 'left',
                'tab' => 'Apple',
			],

            'providers[Apple][enabledForBackend]' => [
                'label' => 'Enabled on backend?',
                'type' => 'checkbox',
                'comment' => 'Can administrators log into the backend with Apple?',
                'default' => 'false',
                'span' => 'right',
                'tab' => 'Apple',
            ],

			'providers[Apple][app_name]' => [
				'label' => 'Application Name',
				'type' => 'text',
				'default' => 'Social Login',
				'comment' => 'This appears on the Apple login screen. Usually your site name.',
				'tab' => 'Apple',
			],

            'providers[Apple][team_id]' => [
                'label' => 'Team ID',
                'type' => 'text',
                'tab' => 'Apple',
            ],

            'providers[Apple][client_id]' => [
                'label' => 'Client ID',
                'type' => 'text',
                'tab' => 'Apple',
            ],

			'providers[Apple][key_id]' => [
				'label' => 'Key ID',
				'type' => 'text',
				'tab' => 'Apple',
			],

			'providers[Apple][key_content]' => [
				'label' => 'Key Content',
				'type' => 'textarea',
				'tab' => 'Apple',
			],
		], 'primary');
	}

    public function redirectToProvider()
    {
        if ($this->getAdapter()->isConnected() )
            return \Redirect::to($this->callback);

        $this->getAdapter()->authenticate();
    }

    /**
     * Handles redirecting off to the login provider
     *
     * @return array ['token' => array $token, 'profile' => \Hybridauth\User\Profile]
     */
	public function handleProviderCallback()
	{
	    $this->getAdapter()->authenticate();

	    $token = $this->getAdapter()->getAccessToken();
        $profile = $this->getAdapter()->getUserProfile();

        // Don't cache anything or successive logins to different accounts
        // will keep logging in to the first account
        $this->getAdapter()->disconnect();

        return [
            'token' => $token,
            'profile' => $profile
        ];
	}
}
