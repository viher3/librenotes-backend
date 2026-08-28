[<< Go Back](Readme.md)

## API Standards

- [Documentation / Swagger](#documentationswagger)
- [Responses](#responses)

###  Documentation / Swagger

We use Api Doc/Swagger fot API documentation.

To document API endpoints using Swagger in the **etc/openapi** folder, follow these steps:

- **Create a YAML file**: Define your API documentation in a YAML or JSON file within the **etc/openapi/{{module_name}}** directory. This file should follow the [OpenAPI Specification](https://swagger.io/specification/).
- **Specify Paths**: Document each endpoint under the paths section. For each endpoint, define the HTTP method, summary, description, parameters, request body, and responses.
- **Use Components**: Reuse common elements like schemas, parameters, and responses by defining them in the components section.
- Split documentation in different files: **one for endpoints** and another **one for custom schemas**. For example:

```yaml
  # etc/openapi/moduleName/paths.yaml

  /moduleName/{id}:

    get:
      summary: Get module entity by id
      parameters:
        - name: id
          in: path
          description: Entity ID
          required: true
          schema:
            type: string

      tags:
        - 📋 Example Module
      responses:
        '200':
          description: OK
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ExampleModuleEntityResponse'
        '404':
          description: Not Found
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ResponseNotFound'
```

```yaml
  # etc/openapi/activityReport/schemas.yaml

  ExampleModuleEntityResponse:
    allOf:
      - $ref: '#/components/schemas/ResponseOk'
      - properties:
          _result:
            $ref: '#/components/schemas/ExampleModuleEntityItem'

  ExampleModuleEntityItem:
    type: object
    properties:
      exampleList:
        type: array
        items:
          $ref: '#/components/schemas/ExampleArrayItem'
      exampleFloat:
        type: float
      exampleArray:
        type: array
        items:
          $ref: '#/components/schemas/IdIntAndName'
```

###  Responses

- Always return a **JSON** response.
- Return response properties in **snake_case**. Example:

```json
{
    "user_id": 1,
    "user_name": "John Doe"
}
```

````json
##################################
# Basic API list/search response #
##################################

HTTP/1.1 200 OK
Content-Type: application/vnd.api+json

{
    "meta": {
      "page": 1,
      "last_page": 5,
      "total": 100,
      "size": 20,
    },
    "data": [
        {
            "type": "articles",
            "id": "3",
            "attributes": {
              "title": "JSON:API paints my bikeshed!",
              "body": "The shortest article. Ever.",
              "created": "2015-05-22T14:56:29.000Z",
              "updated": "2015-05-22T14:56:28.000Z"
            }
        }
    ]
}
````

