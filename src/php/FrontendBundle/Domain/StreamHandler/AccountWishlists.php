<?php

namespace Frontastic\Catwalk\FrontendBundle\Domain\StreamHandler;

use Frontastic\Catwalk\FrontendBundle\Domain\Stream;
use Frontastic\Catwalk\FrontendBundle\Domain\StreamHandler;
use Frontastic\Common\wishlistApiBundle\Domain\WishlistApi;
use Frontastic\Catwalk\ApiCoreBundle\Domain\Context;

class AccountWishlists extends StreamHandler
{
    private $wishlistApi;

    public function __construct(WishlistApi $wishlistApi)
    {
        $this->wishlistApi = $wishlistApi;
    }

    public function getType(): string
    {
        return 'account-wishlists';
    }

    public function handle(Stream $stream, Context $context, array $parameters = [])
    {
        if (!$context->session->loggedIn) {
            return [];
        }

        // While the wishlist ID is also available in the stream configuration
        // this makes sure we always fetch the current wishlists addresses.
        return $this->wishlistApi->getWishlists(
            $context->session->account->accountId
        );
    }
}
