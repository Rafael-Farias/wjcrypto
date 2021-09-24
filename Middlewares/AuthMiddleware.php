<?php

namespace WjCrypto\Middlewares;

use DateTimeImmutable;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Monolog\Logger;
use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;
use WjCrypto\Helpers\JsonResponse;
use WjCrypto\Helpers\LogHelper;
use WjCrypto\Models\Entities\User;
use WjCrypto\Models\Services\UserService;

class AuthMiddleware implements IMiddleware
{
    use JsonResponse;
    use LogHelper;

    private $secretKey = 'ifvDVbqb8g0/Umxz2M2oz.bWa/s8n08gB8kL9qXq8OA5reIEzoRAK';
    private $serverName = 'localhost';

    /**
     * @param Request $request
     */
    public function handle(Request $request): void
    {
        $authorizationHeader = $request->getHeader('Authorization');
        $bearerTokenRegex = '/Bearer\s(\S+)/';
        $basicAuthRegex = '/Basic\s(\S+)/';

        if (url()->getPath() === '/login/') {
            $basicAuthRegexMatchResult = preg_match($basicAuthRegex, $authorizationHeader);

            if ($basicAuthRegexMatchResult === false) {
                $this->sendJsonMessage('Error! The system could not process the Authorization header.', 500);
            }

            switch ($basicAuthRegexMatchResult) {
                case 1:
                    $userService = new UserService();
                    $email = $_SERVER['PHP_AUTH_USER'];
                    $password = $_SERVER['PHP_AUTH_PW'];
                    $user = $userService->getUserByEmailAndPassword($email, $password);

                    $jwt = $this->encodeJwt($user);
                    $message = 'User ' . $user->getEmail() . ' logged in.';
                    $this->registerLog($message, 'login', 'login', Logger::INFO);
                    $this->sendJsonResponse($jwt, 200);
                    break;

                case 0:
                    response()->header('WWW-Authenticate: Basic realm="WjCrypto"');
                    $this->sendJsonMessage('Error! The authorization header is incorrect.', 401);
                    break;
            }
        }

        $matches = [];
        $bearerTokenRegexMatchResult = preg_match($bearerTokenRegex, $authorizationHeader, $matches);
        if ($bearerTokenRegexMatchResult === false) {
            $this->sendJsonMessage('Error! The system could not process the Authorization header.', 500);
        }

        switch ($bearerTokenRegexMatchResult) {
            case 1:
                $this->validateJwt($matches[1]);
                $token = $this->decodeJwt($matches[1]);
                $userService = new UserService();
                $user = $userService->getUser($token->id);
                $newJwt = $this->encodeJwt($user);

                $message = 'User ' . $user->getEmail() . ' updated the JWT Token.';
                $this->registerLog($message, 'login', 'jwtToken', Logger::INFO);

                response()->header('updated-token: ' . $newJwt['jwt']);
                break;

            case 0:
                $this->sendJsonMessage('Error! The authorization token was not provided.', 401);
                break;
        }
    }

    private function encodeJwt(User $user): array
    {
        $issuedAt = new DateTimeImmutable();
        $expire = $issuedAt->modify('+6 minutes')->getTimestamp();
        $username = $user->getEmail();
        $id = $user->getId();

        $data = [
            'iat' => $issuedAt->getTimestamp(),         // Issued at
            'iss' => $this->serverName,                 // Issuer
            'nbf' => $issuedAt->getTimestamp(),         // Not before
            'exp' => $expire,                           // Expire
            'userName' => $username,                    // User name
            'id' => $id                                 // User ID
        ];

        return ['jwt' => JWT::encode($data, $this->secretKey, 'HS512')];
    }

    private function decodeJwt(string $jwt): object
    {
        try {
            $decodedJwt = JWT::decode($jwt, $this->secretKey, ['HS512']);
        } catch (BeforeValidException | ExpiredException | SignatureInvalidException | \UnexpectedValueException | \InvalidArgumentException | \Exception $exception) {
            $exceptionsClassArray = [
                BeforeValidException::class,
                ExpiredException::class,
                SignatureInvalidException::class,
                \UnexpectedValueException::class,
                \InvalidArgumentException::class
            ];
            foreach ($exceptionsClassArray as $exceptionClass) {
                $isInstanceOf = $exception instanceof $exceptionClass;
                if ($isInstanceOf === true) {
                    $this->sendJsonMessage('JWT ' . $exception->getMessage(), 401);
                }
            }
            $this->sendJsonMessage('Error! Invalid token.', 401);
        }
        return $decodedJwt;
    }

    public function getUserId()
    {
        $authorizationHeader = \request()->getHeader('Authorization');
        $bearerTokenRegex = '/Bearer\s(\S+)/';
        $matches = [];
        $bearerTokenRegexMatchResult = preg_match($bearerTokenRegex, $authorizationHeader, $matches);
        if ($bearerTokenRegexMatchResult === false) {
            $this->sendJsonMessage('Error! The system could not process the Authorization header.', 500);
        }
        $this->validateJwt($matches[1]);
        $token = $this->decodeJwt($matches[1]);

        return $token->id;
    }

    private function validateJwt(string $jwt): void
    {
        $token = $this->decodeJwt($jwt);
        $now = new DateTimeImmutable();

        if ($token->iss !== $this->serverName ||
            $token->nbf > $now->getTimestamp() ||
            $token->exp < $now->getTimestamp()) {
            response()->header('HTTP/1.1 401 Unauthorized');
            $this->sendJsonMessage('Error! Invalid Token.', 401);
        }
    }
}