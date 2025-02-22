<?php

class RegisteredStylesheetConnectionQueriesTest extends \Tests\WPGraphQL\TestCase\WPGraphQLTestCase {

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
	public function testRegisteredStylesheetsQueryPagination() {
		wp_set_current_user( $this->admin );

		global $wp_styles;
		do_action( 'wp_enqueue_scripts' );

		$all_registered = array_keys( $wp_styles->registered );

		$query = '
			query testRegisteredStylesheets($first: Int, $after: String, $last: Int, $before: String ) {
				registeredStylesheets(first: $first, last: $last, before: $before, after: $after) {
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

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertIsValidQueryResponse( $actual );

		$nodes = $actual['data']['registeredStylesheets']['nodes'];

		// Test fields for first asset.
		global $wp_styles;
		$expected = $wp_styles->registered[ $nodes[0]['handle'] ];
		codecept_debug( $expected );

		$this->assertEquals( $expected->extra['data'] ?? null, $nodes[0]['extra'] );
		$this->assertEquals( $expected->handle, $nodes[0]['handle'] );
		$this->assertEquals( is_string( $expected->src ) ? $expected->src : null, $nodes[0]['src'] );
		$this->assertEquals( $expected->ver ?: $wp_styles->default_version, $nodes[0]['version'] );

		// Get first two registeredStylesheets
		$variables['first'] = 2;
		$variables['after'] = null;

		$expected = array_slice( $nodes, 0, $variables['first'], true );
		$actual   = $this->graphql( compact( 'query', 'variables' ) );

		codecept_debug( [
			'expected' => $expected,
			'actual'   => $actual['data']['registeredStylesheets']['nodes'],
		]);

		$this->assertEqualSets( $expected, $actual['data']['registeredStylesheets']['nodes'] );

		// Test with empty `after`.
		$variables['after'] = '';
		$actual             = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertEqualSets( $expected, $actual['data']['registeredStylesheets']['nodes'] );

		// Get last two registeredStylesheets
		$variables = [
			'first'  => null,
			'after'  => null,
			'last'   => 2,
			'before' => null,
		];

		$expected = array_slice( $nodes, count( $nodes ) - $variables['last'], null, true );
		$actual   = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertEqualSets( $expected, $actual['data']['registeredStylesheets']['nodes'] );

		// Test with empty `before`.
		$variables['before'] = '';
		$actual              = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertEqualSets( $expected, $actual['data']['registeredStylesheets']['nodes'] );
	}

}
