<?php

namespace WjCrypto\middlewares;

use DateTimeImmutable;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;
use WjCrypto\Models\Entities\User;
use WjCrypto\Models\Services\UserService;

class AuthMiddleware implements IMiddleware
{

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
                $response = [
                    'message' => 'Error! The system could not process the Authorization header.'
                ];
                $this->sendJsonResponse($response, 500);
            }

            switch ($basicAuthRegexMatchResult) {
                case 1:
                    $userService = new UserService();
                    $validationResult = $userService->validateEmailAndPasswordThenMatchesPersistedUser();
                    if (is_array($validationResult)) {
                        $this->sendJsonResponse($validationResult['message'], $validationResult['httpResponseCode']);
                    }
                    $jwt = $this->encodeJwt($validationResult);
                    $this->sendJsonResponse($jwt, 200);
                    break;

                case 0:
                    response()->header('WWW-Authenticate: Basic realm="WjCrypto"');
                    $response = [
                        'message' => 'Error! The authorization header is incorrect.'
                    ];
                    $this->sendJsonResponse($response, 401);
                    break;
            }
        }

        $matches = [];
        $bearerTokenRegexMatchResult = preg_match($bearerTokenRegex, $authorizationHeader, $matches);
        if ($bearerTokenRegexMatchResult === false) {
            $response = [
                'message' => 'Error! The system could not process the Authorization header.'
            ];
            $this->sendJsonResponse($response, 500);
        }

        switch ($bearerTokenRegexMatchResult) {
            case 1:
                $token = $this->decodeJwt($matches[1]);
                $now = new DateTimeImmutable();

                if ($token->iss !== $this->serverName ||
                    $token->nbf > $now->getTimestamp() ||
                    $token->exp < $now->getTimestamp()) {
                    response()->header('HTTP/1.1 401 Unauthorized');
                    $response = [
                        'message' => 'Error! Invalid Token.'
                    ];
                    $this->sendJsonResponse($response, 401);
                }

                $userService = new UserService();
                $user = $userService->getUser($token->id);
                $newJwt = $this->encodeJwt($user['message']);
                response()->header('updated-token: ' . $newJwt['jwt']);
                break;

            case 0:
                $response = [
                    'message' => 'Error! The authorization token was not provided.'
                ];
                $this->sendJsonResponse($response, 401);
                break;
        }
    }

    private function sendJsonResponse(array $dataArray, int $httpResponseCode): void
    {
        response()->httpCode($httpResponseCode);
        response()->json($dataArray);
    }

    private function encodeJwt(User $user): array
    {
        $issuedAt = new DateTimeImmutable();
        $expire = $issuedAt->modify('+6 minutes')->getTimestamp();
        $username = $user->getEmail();
        $id = $user->getId();

        $data = [
            'iat' => $issuedAt->getTimestamp(),         // Issued at
            'iss' => $this->serverName,                       // Issuer
            'nbf' => $issuedAt->getTimestamp(),         // Not before
            'exp' => $expire,                           // Expire
            'userName' => $username,
            'id' => $id
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
                    $response = [
                        'message' => $exception->getMessage()
                    ];
                    $this->sendJsonResponse($response, 401);
                }
            }

            $response = [
                'message' => 'Error! Invalid token.'
            ];
            $this->sendJsonResponse($response, 401);
        }
        return $decodedJwt;
    }
}