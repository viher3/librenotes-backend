<?php

namespace App\Tests\Unit\User\Application\Search;

use App\User\Application\Search\SearchUserHandler;
use App\User\Application\Search\SearchUserQuery;
use App\User\Application\Search\SearchUserResponse;
use App\User\Domain\UserRepository;
use PHPUnit\Framework\TestCase;

class SearchUserHandlerTest extends TestCase
{
    private UserRepository $userRepository;
    private SearchUserHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->handler = new SearchUserHandler($this->userRepository);
    }

    public function testInvokeReturnsSearchUserResponse(): void
    {
        $filters = ['username' => 'value1'];
        $queryParams = [
            $filters,
            1,
            10,
            'created_at',
            'desc'
        ];

        $query = $this->createMock(SearchUserQuery::class);
        $query->method('getAll')->willReturn($queryParams);
        $query->method('getFilters')->willReturn($filters);
        $users = ['user1', 'user2'];

        $totalUsers = 2;

        $this->userRepository
            ->method('search')
            ->with(...$queryParams)
            ->willReturn($users);

        $this->userRepository
            ->method('searchCount')
            ->with($filters)
            ->willReturn($totalUsers);

        $response = ($this->handler)($query);

        $this->assertInstanceOf(SearchUserResponse::class, $response);
        $this->assertEquals($users, $response->getUsers());
        $this->assertEquals($totalUsers, $response->getTotal());
    }
}