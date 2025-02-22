<?php

class RegisteredScriptConnectionQueriesTest extends \Tests\WPGraphQL\TestCase\WPGraphQLTestCase {

	private $admin;

	public function setUp(): void {
		parent::setUp();
		$this->admin = $this->factory()->user->create( [ 'role' => 'administrator' ] );
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Tests querying for plugins with pagination args.
	 */
	public function testRegisteredScriptsQueryPagination() {
		wp_set_current_user( $this->admin );

		$query = '
			query testRegisteredScripts($first: Int, $after: String, $last: Int, $before: String ) {
				registeredScripts(first: $first, last: $last, before: $before, after: $after) {
					pageInfo {
						endCursor
						hasNextPage
						hasPreviousPage
						startCursor
					}
					nodes {
						extra
						handle
						id
						src
						version
					}
				}
			}
		';

		// Get all for comparison
		$variables = [
			'first'  => 500,
			'after'  => null,
			'last'   => null,
			'before' => null,
		];
		$actual    = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertIsValidQueryResponse( $actual );

		$nodes = $actual['data']['registeredScripts']['nodes'];

		// Test fields for first asset.
		global $wp_scripts;
		$expected = $wp_scripts->registered[ $nodes[0]['handle'] ];

		$this->assertEquals( $expected->extra['data'], $nodes[0]['extra'] );
		$this->assertEquals( $expected->handle, $nodes[0]['handle'] );
		$this->assertEquals( $expected->src, $nodes[0]['src'] );
		$this->assertEquals( $expected->ver ?: $wp_scripts->default_version, $nodes[0]['version'] );

		// Get first two registeredScripts
		$variables['first'] = 2;
		$variables['after'] = null;

		$expected = array_slice( $nodes, 0, $variables['first'], true );
		$actual   = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertEqualSets( $expected, $actual['data']['registeredScripts']['nodes'] );

		// Test with empty `after`.
		$variables['after'] = '';
		$actual             = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertEqualSets( $expected, $actual['data']['registeredScripts']['nodes'] );

		// Get last two registeredScripts
		$variables = [
			'first'  => null,
			'after'  => null,
			'last'   => 2,
			'before' => null,
		];

		$expected = array_slice( $nodes, count( $nodes ) - $variables['last'], null, true );
		$actual   = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertEqualSets( $expected, $actual['data']['registeredScripts']['nodes'] );

		// Test with empty `before`.
		$variables['before'] = '';
		$actual              = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertEqualSets( $expected, $actual['data']['registeredScripts']['nodes'] );
	}

}
