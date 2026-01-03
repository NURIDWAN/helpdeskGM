<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Helpdesk System API",
 *     description="API Documentation for Helpdesk System - GA Maintenance",
 *     @OA\Contact(
 *         email="admin@gmail.com"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="/api/v1",
 *     description="API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your bearer token"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="API Endpoints for user authentication"
 * )
 * 
 * @OA\Tag(
 *     name="Tickets",
 *     description="API Endpoints for ticket management"
 * )
 * 
 * @OA\Tag(
 *     name="Work Orders",
 *     description="API Endpoints for work order management"
 * )
 * 
 * @OA\Tag(
 *     name="Work Reports",
 *     description="API Endpoints for work report management"
 * )
 * 
 * @OA\Tag(
 *     name="Daily Records",
 *     description="API Endpoints for daily record management"
 * )
 * 
 * @OA\Tag(
 *     name="Dashboard",
 *     description="API Endpoints for dashboard metrics"
 * )
 * 
 * @OA\Tag(
 *     name="Users",
 *     description="API Endpoints for user management"
 * )
 * 
 * @OA\Tag(
 *     name="Branches",
 *     description="API Endpoints for branch management"
 * )
 * 
 * @OA\Tag(
 *     name="Roles",
 *     description="API Endpoints for role management"
 * )
 */
class OpenApiSpec
{
}
