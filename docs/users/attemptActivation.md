<a id="attemptActivation"></a>
###attemptActivation($activationCode)

----------

Activate a user.

Parameters                   | Type            | Default       | Description
:--------------------------- | :-------------: | :------------ | :--------------
`$activationCode`            | string          | none          | Activation Code

`returns` bool

####Example

	try
	{
		$user = Sentry::getUserProvider()->findById(1);

		if ($user->attemptActivation('8f1Z7wA4uVt7VemBpGSfaoI9mcjdEwtK8elCnQOb'))
		{
			echo 'Activated';
		}
		else
		{
			echo 'Activation failed.';
		}
	}
	catch (Cartalyst\Sentry\Users\UserNotFoundException $e)
	{
		echo 'User does not exist.';
	}